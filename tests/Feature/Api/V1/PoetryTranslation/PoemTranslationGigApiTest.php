<?php

use Domain\Event\Models\EventCategory;
use Domain\Gig\Models\Gig;
use Domain\Gig\Models\GigApplication;
use Domain\Gig\Models\GigNegotiation;
use Domain\Gig\Models\TranslationComment;
use Domain\Gig\Models\TranslationJob;
use Domain\Poetry\Models\Poem;
use Domain\Poetry\Models\PoemCategory;
use Domain\Poetry\Models\PoemType;
use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function translationOwner(): User
{
    return User::factory()->poetOrganizer()->create([
        'email_verified_at' => now(),
    ]);
}

function translationPoemCategory(): PoemCategory
{
    $category = PoemCategory::query()->create([
        'slug' => 'test-category',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $category->translations()->create([
        'locale' => 'it',
        'name' => 'Categoria test',
    ]);

    return $category;
}

function translationPoemType(): PoemType
{
    $type = PoemType::query()->create([
        'slug' => 'test-type',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $type->translations()->create([
        'locale' => 'it',
        'name' => 'Tipo test',
    ]);

    return $type;
}

function makeTranslationPoem(User $user, PoemCategory $category, PoemType $type, array $overrides = []): Poem
{
    return Poem::query()->create(array_merge([
        'user_id' => $user->id,
        'poem_category_id' => $category->id,
        'poem_type_id' => $type->id,
        'title' => 'Poesia test',
        'slug' => 'poesia-test',
        'content' => 'Testo della poesia.',
        'language' => 'it',
        'status' => 'published',
        'is_public' => true,
        'published_at' => now(),
    ], $overrides));
}

it('allows the poem owner to create a translation gig', function () {
    $owner = translationOwner();
    Sanctum::actingAs($owner);

    $category = translationPoemCategory();
    $type = translationPoemType();
    $poem = makeTranslationPoem($owner, $category, $type);

    $response = $this->postJson(route('api.v1.poems.translation-gigs.store', $poem), [
        'target_language' => 'Japanese',
        'proposed_price' => 40,
        'currency' => 'EUR',
        'deadline' => now()->addWeek()->toDateString(),
        'special_requirements' => 'Mantenere il registro lirico.',
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.translation_gig.poem_id', $poem->id)
        ->assertJsonPath('data.translation_gig.target_language', 'Japanese')
        ->assertJsonPath('data.translation_gig.proposed_price', '40.00')
        ->assertJsonPath('data.translation_gig.currency', 'EUR');

    $this->assertDatabaseHas('gigs', [
        'giggable_type' => Poem::class,
        'giggable_id' => $poem->id,
        'type' => 'translator',
        'language' => 'Japanese',
        'proposed_price' => 40.00,
    ]);
});

it('lists translation gigs for the poem owner', function () {
    $owner = translationOwner();
    Sanctum::actingAs($owner);

    $category = translationPoemCategory();
    $type = translationPoemType();
    $poem = makeTranslationPoem($owner, $category, $type);

    $poem->gigs()->create([
        'type' => 'translator',
        'quantity' => 1,
        'filled_quantity' => 0,
        'language' => 'French',
        'proposed_price' => 55,
        'currency' => 'EUR',
        'status' => 'open',
        'opened_at' => now(),
        'created_by_user_id' => $owner->id,
        'updated_by_user_id' => $owner->id,
    ]);

    $response = $this->getJson(route('api.v1.poems.translation-gigs.index', $poem));

    $response
        ->assertOk()
        ->assertJsonCount(1, 'data.translation_gigs')
        ->assertJsonPath('data.translation_gigs.0.target_language', 'French');
});

it('allows a translator to apply to a poem translation gig', function () {
    $owner = translationOwner();
    $translator = User::factory()->poetOrganizer()->create([
        'email_verified_at' => now(),
    ]);
    Sanctum::actingAs($translator);

    $category = translationPoemCategory();
    $type = translationPoemType();
    $poem = makeTranslationPoem($owner, $category, $type);

    $gig = $poem->gigs()->create([
        'type' => 'translator',
        'quantity' => 1,
        'filled_quantity' => 0,
        'language' => 'Spanish',
        'proposed_price' => 45,
        'currency' => 'EUR',
        'status' => 'open',
        'opened_at' => now(),
        'created_by_user_id' => $owner->id,
        'updated_by_user_id' => $owner->id,
    ]);

    $response = $this->postJson(route('api.v1.gigs.applications.store', $gig), [
        'message' => 'Mi occupo di traduzione poetica it-es.',
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.application.gig_id', $gig->id)
        ->assertJsonPath('data.application.status', 'pending');
});

it('allows the poem owner to open a negotiation from an application', function () {
    $owner = translationOwner();
    Sanctum::actingAs($owner);

    $translator = User::factory()->poetOrganizer()->create([
        'email_verified_at' => now(),
    ]);

    $category = translationPoemCategory();
    $type = translationPoemType();
    $poem = makeTranslationPoem($owner, $category, $type);

    $gig = $poem->gigs()->create([
        'type' => 'translator',
        'quantity' => 1,
        'filled_quantity' => 0,
        'language' => 'German',
        'proposed_price' => 60,
        'currency' => 'EUR',
        'deadline' => now()->addDays(10),
        'status' => 'open',
        'opened_at' => now(),
        'created_by_user_id' => $owner->id,
        'updated_by_user_id' => $owner->id,
    ]);

    $application = GigApplication::query()->create([
        'gig_id' => $gig->id,
        'user_id' => $translator->id,
        'status' => 'pending',
    ]);

    $response = $this->postJson(route('api.v1.gigs.negotiations.store', [
        'gig' => $gig,
        'application' => $application,
    ]), [
        'price' => 65,
        'currency' => 'EUR',
        'deadline' => now()->addDays(7)->toDateString(),
        'note' => 'Prima proposta con consegna rapida.',
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.negotiation.gig_id', $gig->id)
        ->assertJsonPath('data.negotiation.gig_application_id', $application->id)
        ->assertJsonPath('data.negotiation.status', 'open');

    $this->assertDatabaseHas('gig_negotiations', [
        'gig_id' => $gig->id,
        'gig_application_id' => $application->id,
        'status' => 'open',
    ]);
});

it('allows the translator to add a counter offer event to the negotiation', function () {
    $owner = translationOwner();
    $translator = User::factory()->poetOrganizer()->create([
        'email_verified_at' => now(),
    ]);
    Sanctum::actingAs($translator);

    $category = translationPoemCategory();
    $type = translationPoemType();
    $poem = makeTranslationPoem($owner, $category, $type);

    $gig = $poem->gigs()->create([
        'type' => 'translator',
        'quantity' => 1,
        'filled_quantity' => 0,
        'language' => 'English',
        'proposed_price' => 50,
        'currency' => 'EUR',
        'status' => 'filled',
        'opened_at' => now(),
        'created_by_user_id' => $owner->id,
        'updated_by_user_id' => $owner->id,
    ]);

    $application = GigApplication::query()->create([
        'gig_id' => $gig->id,
        'user_id' => $translator->id,
        'status' => 'accepted',
    ]);

    $negotiation = GigNegotiation::query()->create([
        'gig_id' => $gig->id,
        'gig_application_id' => $application->id,
        'owner_user_id' => $owner->id,
        'translator_user_id' => $translator->id,
        'status' => 'open',
        'currency' => 'EUR',
    ]);

    $response = $this->postJson(route('api.v1.gig-negotiations.events.store', $negotiation), [
        'event_type' => 'counter_offer',
        'price' => 70,
        'currency' => 'EUR',
        'note' => 'Posso farlo a questo prezzo con revisione inclusa.',
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.event.event_type', 'counter_offer')
        ->assertJsonPath('data.event.price', 70);
});

it('allows the owner to agree a negotiation and rejects competing applications', function () {
    $owner = translationOwner();
    Sanctum::actingAs($owner);

    $translatorOne = User::factory()->poetOrganizer()->create(['email_verified_at' => now()]);
    $translatorTwo = User::factory()->poetOrganizer()->create(['email_verified_at' => now()]);

    $category = translationPoemCategory();
    $type = translationPoemType();
    $poem = makeTranslationPoem($owner, $category, $type);

    $gig = $poem->gigs()->create([
        'type' => 'translator',
        'quantity' => 1,
        'filled_quantity' => 0,
        'language' => 'Portuguese',
        'proposed_price' => 45,
        'currency' => 'EUR',
        'status' => 'filled',
        'opened_at' => now(),
        'created_by_user_id' => $owner->id,
        'updated_by_user_id' => $owner->id,
    ]);

    $applicationOne = GigApplication::query()->create([
        'gig_id' => $gig->id,
        'user_id' => $translatorOne->id,
        'status' => 'accepted',
    ]);

    $applicationTwo = GigApplication::query()->create([
        'gig_id' => $gig->id,
        'user_id' => $translatorTwo->id,
        'status' => 'pending',
    ]);

    $negotiation = GigNegotiation::query()->create([
        'gig_id' => $gig->id,
        'gig_application_id' => $applicationOne->id,
        'owner_user_id' => $owner->id,
        'translator_user_id' => $translatorOne->id,
        'status' => 'open',
        'currency' => 'EUR',
    ]);

    $negotiation->events()->create([
        'author_user_id' => $owner->id,
        'event_type' => 'owner_offer',
        'price' => 55,
        'currency' => 'EUR',
        'note' => 'Offerta finale',
    ]);

    $response = $this->postJson(route('api.v1.gig-negotiations.agree', $negotiation));

    $response
        ->assertOk()
        ->assertJsonPath('data.negotiation.status', 'agreed')
        ->assertJsonPath('data.negotiation.agreed_price', 55);

    $this->assertDatabaseHas('gig_negotiations', [
        'id' => $negotiation->id,
        'status' => 'agreed',
    ]);

    $this->assertDatabaseHas('gig_applications', [
        'id' => $applicationOne->id,
        'status' => 'confirmed',
    ]);

    $this->assertDatabaseHas('gig_applications', [
        'id' => $applicationTwo->id,
        'status' => 'rejected',
    ]);
});

it('creates a translation job when a negotiation is agreed', function () {
    $owner = translationOwner();
    Sanctum::actingAs($owner);

    $translator = User::factory()->poetOrganizer()->create(['email_verified_at' => now()]);
    $category = translationPoemCategory();
    $type = translationPoemType();
    $poem = makeTranslationPoem($owner, $category, $type);

    $gig = $poem->gigs()->create([
        'type' => 'translator',
        'quantity' => 1,
        'filled_quantity' => 0,
        'language' => 'Dutch',
        'proposed_price' => 42,
        'currency' => 'EUR',
        'status' => 'filled',
        'opened_at' => now(),
        'created_by_user_id' => $owner->id,
        'updated_by_user_id' => $owner->id,
    ]);

    $application = GigApplication::query()->create([
        'gig_id' => $gig->id,
        'user_id' => $translator->id,
        'status' => 'accepted',
    ]);

    $negotiation = GigNegotiation::query()->create([
        'gig_id' => $gig->id,
        'gig_application_id' => $application->id,
        'owner_user_id' => $owner->id,
        'translator_user_id' => $translator->id,
        'status' => 'open',
        'currency' => 'EUR',
    ]);

    $negotiation->events()->create([
        'author_user_id' => $owner->id,
        'event_type' => 'owner_offer',
        'price' => 48,
        'currency' => 'EUR',
    ]);

    $this->postJson(route('api.v1.gig-negotiations.agree', $negotiation))
        ->assertOk();

    $this->assertDatabaseHas('translation_jobs', [
        'gig_negotiation_id' => $negotiation->id,
        'gig_id' => $gig->id,
        'poem_id' => $poem->id,
        'translator_user_id' => $translator->id,
        'owner_user_id' => $owner->id,
        'target_language' => 'Dutch',
        'status' => 'in_progress',
    ]);
});

it('allows the translator to update and submit a translation job', function () {
    $owner = translationOwner();
    $translator = User::factory()->poetOrganizer()->create(['email_verified_at' => now()]);
    Sanctum::actingAs($translator);

    $category = translationPoemCategory();
    $type = translationPoemType();
    $poem = makeTranslationPoem($owner, $category, $type);
    $gig = Gig::query()->create([
        'giggable_type' => Poem::class,
        'giggable_id' => $poem->id,
        'type' => 'translator',
        'quantity' => 1,
        'filled_quantity' => 1,
        'language' => 'Polish',
        'status' => 'filled',
        'created_by_user_id' => $owner->id,
        'updated_by_user_id' => $owner->id,
    ]);
    $application = GigApplication::query()->create([
        'gig_id' => $gig->id,
        'user_id' => $translator->id,
        'status' => 'confirmed',
    ]);
    $negotiation = GigNegotiation::query()->create([
        'gig_id' => $gig->id,
        'gig_application_id' => $application->id,
        'owner_user_id' => $owner->id,
        'translator_user_id' => $translator->id,
        'status' => 'agreed',
        'agreed_price' => 60,
        'currency' => 'EUR',
    ]);
    $job = TranslationJob::query()->create([
        'gig_id' => $gig->id,
        'gig_negotiation_id' => $negotiation->id,
        'poem_id' => $poem->id,
        'translator_user_id' => $translator->id,
        'owner_user_id' => $owner->id,
        'source_language' => 'it',
        'target_language' => 'Polish',
        'agreed_price' => 60,
        'currency' => 'EUR',
        'status' => 'in_progress',
    ]);

    $this->patchJson(route('api.v1.translation-jobs.update', $job), [
        'translated_content' => 'Przetlumaczony tekst.',
    ])
        ->assertOk()
        ->assertJsonPath('data.translation_job.translated_content', 'Przetlumaczony tekst.');

    $this->postJson(route('api.v1.translation-jobs.submit', $job))
        ->assertOk()
        ->assertJsonPath('data.translation_job.status', 'submitted');
});

it('allows the owner to request changes and approve a translation job', function () {
    $owner = translationOwner();
    Sanctum::actingAs($owner);

    $translator = User::factory()->poetOrganizer()->create(['email_verified_at' => now()]);
    $category = translationPoemCategory();
    $type = translationPoemType();
    $poem = makeTranslationPoem($owner, $category, $type);
    $gig = Gig::query()->create([
        'giggable_type' => Poem::class,
        'giggable_id' => $poem->id,
        'type' => 'translator',
        'quantity' => 1,
        'filled_quantity' => 1,
        'language' => 'Romanian',
        'status' => 'filled',
        'created_by_user_id' => $owner->id,
        'updated_by_user_id' => $owner->id,
    ]);
    $application = GigApplication::query()->create([
        'gig_id' => $gig->id,
        'user_id' => $translator->id,
        'status' => 'confirmed',
    ]);
    $negotiation = GigNegotiation::query()->create([
        'gig_id' => $gig->id,
        'gig_application_id' => $application->id,
        'owner_user_id' => $owner->id,
        'translator_user_id' => $translator->id,
        'status' => 'agreed',
        'currency' => 'EUR',
    ]);
    $job = TranslationJob::query()->create([
        'gig_id' => $gig->id,
        'gig_negotiation_id' => $negotiation->id,
        'poem_id' => $poem->id,
        'translator_user_id' => $translator->id,
        'owner_user_id' => $owner->id,
        'source_language' => 'it',
        'target_language' => 'Romanian',
        'translated_content' => 'Text tradus.',
        'status' => 'submitted',
        'submitted_at' => now(),
    ]);

    $this->postJson(route('api.v1.translation-jobs.request-changes', $job))
        ->assertOk()
        ->assertJsonPath('data.translation_job.status', 'changes_requested');

    $this->postJson(route('api.v1.translation-jobs.approve', $job))
        ->assertOk()
        ->assertJsonPath('data.translation_job.status', 'approved');

    $this->assertDatabaseHas('gigs', [
        'id' => $gig->id,
        'status' => 'closed',
    ]);
});

it('allows both parties to comment on a translation job and resolve comments', function () {
    $owner = translationOwner();
    $translator = User::factory()->poetOrganizer()->create(['email_verified_at' => now()]);
    Sanctum::actingAs($translator);

    $category = translationPoemCategory();
    $type = translationPoemType();
    $poem = makeTranslationPoem($owner, $category, $type);
    $gig = Gig::query()->create([
        'giggable_type' => Poem::class,
        'giggable_id' => $poem->id,
        'type' => 'translator',
        'quantity' => 1,
        'filled_quantity' => 1,
        'language' => 'Swedish',
        'status' => 'filled',
        'created_by_user_id' => $owner->id,
        'updated_by_user_id' => $owner->id,
    ]);
    $application = GigApplication::query()->create([
        'gig_id' => $gig->id,
        'user_id' => $translator->id,
        'status' => 'confirmed',
    ]);
    $negotiation = GigNegotiation::query()->create([
        'gig_id' => $gig->id,
        'gig_application_id' => $application->id,
        'owner_user_id' => $owner->id,
        'translator_user_id' => $translator->id,
        'status' => 'agreed',
        'currency' => 'EUR',
    ]);
    $job = TranslationJob::query()->create([
        'gig_id' => $gig->id,
        'gig_negotiation_id' => $negotiation->id,
        'poem_id' => $poem->id,
        'translator_user_id' => $translator->id,
        'owner_user_id' => $owner->id,
        'source_language' => 'it',
        'target_language' => 'Swedish',
        'translated_content' => 'Oversatt text.',
        'status' => 'in_progress',
    ]);

    $response = $this->postJson(route('api.v1.translation-jobs.comments.store', $job), [
        'comment_target' => 'translated_text',
        'target_locator' => 'line:1',
        'content' => 'Qui forse possiamo scegliere una parola più lirica.',
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.comment.comment_target', 'translated_text')
        ->assertJsonPath('data.comment.status', 'open');

    $commentId = $response->json('data.comment.id');

    Sanctum::actingAs($owner);

    $this->getJson(route('api.v1.translation-jobs.comments.index', $job))
        ->assertOk()
        ->assertJsonCount(1, 'data.comments');

    $this->postJson(route('api.v1.translation-jobs.comments.resolve', [
        'job' => $job,
        'comment' => $commentId,
    ]))
        ->assertOk()
        ->assertJsonPath('data.comment.status', 'resolved');
});
