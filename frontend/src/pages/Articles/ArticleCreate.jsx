import { useParams } from 'react-router-dom'
import MainLayout from '../../components/layout/MainLayout.jsx'
import pageStyles from '../shared/ContentPageShell.module.css'
import ArticleForm from '../../components/news/ArticleForm.jsx'

export default function ArticleCreate() {
  const { slug } = useParams()

  return (
    <MainLayout themeTone="articles">
      <div className={pageStyles.articlesPage}>
        <ArticleForm slug={slug} />
      </div>
    </MainLayout>
  )
}
