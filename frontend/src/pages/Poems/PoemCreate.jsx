import MainLayout from '../../components/layout/MainLayout.jsx'
import pageStyles from '../shared/ContentPageShell.module.css'
import PoemForm from '../../components/poems/PoemForm.jsx'

export default function PoemCreate() {
  return (
    <MainLayout themeTone="poems">
      <div className={pageStyles.poemsPage}>
        <PoemForm />
      </div>
    </MainLayout>
  )
}
