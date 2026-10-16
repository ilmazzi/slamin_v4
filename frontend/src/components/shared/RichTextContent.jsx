import { sanitizeRichText } from '../../app/content/richText.js'
import styles from './RichTextContent.module.css'

export default function RichTextContent({ content, className = '', lang }) {
  if (!content) {
    return null
  }

  return (
    <div
      className={[styles.content, className].filter(Boolean).join(' ')}
      lang={lang}
      dangerouslySetInnerHTML={{ __html: sanitizeRichText(content) }}
    />
  )
}
