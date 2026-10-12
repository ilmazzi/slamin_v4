import { useI18n } from '../../app/hooks/useI18n.js'
import MainLayout from '../../components/layout/MainLayout.jsx'

export default function PlaceholderPage({
  titleKey = 'common.coming_soon.default_title',
  descriptionKey = 'common.coming_soon.default_description',
  title,
  description,
}) {
  const { t } = useI18n()
  const resolvedTitle = title ?? t(titleKey)
  const resolvedDescription = description ?? t(descriptionKey)

  return (
    <MainLayout>
      <section
        style={{
          maxWidth: 920,
          margin: '0 auto',
          padding: '56px 20px 64px',
          color: 'var(--color-black)',
        }}
      >
        <h1 style={{ margin: 0, fontSize: '2rem', lineHeight: 1.2 }}>{resolvedTitle}</h1>
        <p style={{ marginTop: 16, fontSize: '1.05rem', lineHeight: 1.5 }}>{resolvedDescription}</p>
      </section>
    </MainLayout>
  )
}
