import MainLayout from '../../components/layout/MainLayout.jsx'

export default function PlaceholderPage({
  title = 'Pagina in preparazione',
  description = 'Questa sezione non e ancora disponibile nel frontend corrente.',
}) {
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
        <h1 style={{ margin: 0, fontSize: '2rem', lineHeight: 1.2 }}>{title}</h1>
        <p style={{ marginTop: 16, fontSize: '1.05rem', lineHeight: 1.5 }}>{description}</p>
      </section>
    </MainLayout>
  )
}
