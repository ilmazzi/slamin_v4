import { useParams } from 'react-router-dom'
import MainLayout from '../../components/layout/MainLayout.jsx'
import EventCreateForm from '../../components/events/EventCreateForm.jsx'
import pageStyles from '../shared/ContentPageShell.module.css'

export default function EventCreatePage() {
  const { eventId } = useParams()

  return (
    <MainLayout themeTone="events">
      <div className={pageStyles.eventPage}>
        <EventCreateForm eventSlug={eventId || null} />
      </div>
    </MainLayout>
  )
}
