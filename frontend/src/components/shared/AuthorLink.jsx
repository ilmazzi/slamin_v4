import { Link } from 'react-router-dom'

function profilePath(nickname) {
  const slug = String(nickname || '').trim()
  return slug ? `/u/${encodeURIComponent(slug)}` : null
}

export default function AuthorLink({ author, className, ariaLabel, children }) {
  const href = profilePath(author?.nickname)
  if (!href) {
    return <span className={className}>{children}</span>
  }

  return (
    <Link to={href} className={className} aria-label={ariaLabel}>
      {children}
    </Link>
  )
}
