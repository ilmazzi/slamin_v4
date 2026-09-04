import MainLayout from '../../components/layout/MainLayout.jsx'
import EventCreateForm from '../../components/events/EventCreateForm.jsx'
import pageStyles from '../shared/ContentPageShell.module.css'

export default function EventCreatePage() {
  return (
    <MainLayout themeTone="events">
      <div className={pageStyles.eventPage}>
        <EventCreateForm />
      </div>
    </MainLayout>
  )
}
