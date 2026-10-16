import { useParams } from 'react-router-dom'
import MainLayout from '../../components/layout/MainLayout.jsx'
import pageStyles from '../shared/ContentPageShell.module.css'
import PoemForm from '../../components/poems/PoemForm.jsx'

export default function PoemCreate() {
  const { slug } = useParams()

  return (
    <MainLayout themeTone="poems">
      <div className={pageStyles.poemsPage}>
        <PoemForm slug={slug} />
      </div>
    </MainLayout>
  )
}
