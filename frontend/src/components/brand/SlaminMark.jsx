export default function SlaminMark({ className, title }) {
  return (
    <svg
      className={className}
      width="27"
      height="39"
      viewBox="0 0 27 39"
      fill="none"
      xmlns="http://www.w3.org/2000/svg"
      aria-hidden={title ? undefined : true}
      role={title ? 'img' : undefined}
    >
      {title ? <title>{title}</title> : null}
      <path d="M0 0V29.3142H12.2315L27 39V29.3142V9.68174V0H0Z" fill="#E30613" />
    </svg>
  )
}
