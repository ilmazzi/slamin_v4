import MainLayout from '../../components/layout/MainLayout.jsx'
import pageStyles from '../shared/ContentPageShell.module.css'
import ArticleForm from '../../components/news/ArticleForm.jsx'

export default function ArticleCreate() {
  return (
    <MainLayout themeTone="articles">
      <div className={pageStyles.articlesPage}>
        <ArticleForm />
      </div>
    </MainLayout>
  )
}
