import { emptyGig } from './eventFormDefaults.js'
import { createEventGig, deleteEventGig, updateEventGig } from '../api/gigs.js'

const GIG_FIELDS = Object.keys(emptyGig)
const FORM_STATUSES = ['draft', 'open', 'closed', 'cancelled']

const isPositiveAmount = (value) => Number(String(value ?? '').replace(',', '.')) > 0
const isFilled = (value) => String(value ?? '').trim() !== ''

/**
 * An offer is included only when its field is filled in: there is no separate toggle.
 */
export function gigOfferFlags({ proposed_price, travel_expenses_max, accommodation_details }) {
  const accommodation = isFilled(accommodation_details)

  return {
    cachet_enabled: isPositiveAmount(proposed_price),
    travel_expenses_enabled: isPositiveAmount(travel_expenses_max),
    accommodation_enabled: accommodation,
    hospitality_required: accommodation,
  }
}

export function gigToFormRow(gig) {
  const row = { ...emptyGig, id: gig.id, applications_count: gig.applications_count ?? 0 }
  GIG_FIELDS.forEach((field) => {
    if (gig[field] !== null && gig[field] !== undefined) {
      row[field] = gig[field]
    }
  })
  row.deadline = gig.deadline ? String(gig.deadline).slice(0, 10) : ''
  row.status = FORM_STATUSES.includes(gig.status) ? gig.status : 'open'
  return row
}

export function gigRowPayload(row) {
  const payload = {}
  GIG_FIELDS.forEach((field) => {
    payload[field] = row[field]
  })
  payload.quantity = Number(row.quantity || 1)
  payload.deadline = row.deadline || null
  return { ...payload, ...gigOfferFlags(row) }
}

const payloadKey = (row) => JSON.stringify(gigRowPayload(row))

export function snapshotGigRows(rows) {
  return new Map(rows.filter((row) => row.id).map((row) => [row.id, payloadKey(row)]))
}

/**
 * Applies deletions, creations and updates one by one. `snapshot` and `deletedIds`
 * are mutated as each request succeeds, so a retry after a failure never repeats work.
 */
export async function syncEventGigs({ eventId, rows, snapshot, deletedIds, token }) {
  const nextRows = [...rows]

  try {
    for (const id of [...deletedIds]) {
      await deleteEventGig(eventId, id, { token })
      deletedIds.delete(id)
      snapshot.delete(id)
    }

    for (let index = 0; index < nextRows.length; index += 1) {
      const row = nextRows[index]
      if (!row.id) {
        const created = await createEventGig(eventId, gigRowPayload(row), { token })
        if (created?.id) {
          nextRows[index] = { ...row, id: created.id }
          snapshot.set(created.id, payloadKey(nextRows[index]))
        }
      } else if (snapshot.get(row.id) !== payloadKey(row)) {
        await updateEventGig(eventId, row.id, gigRowPayload(row), { token })
        snapshot.set(row.id, payloadKey(row))
      }
    }
  } catch (error) {
    return { rows: nextRows, error }
  }

  return { rows: nextRows, error: null }
}
