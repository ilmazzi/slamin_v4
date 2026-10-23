import styles from './VoteControl.module.css'

export default function VoteControl({
  score = 0,
  myVote = null,
  onVote,
  disabled = false,
  upvoteLabel,
  downvoteLabel,
}) {
  const nextValue = (value) => (Number(myVote) === value ? 0 : value)

  return (
    <div className={styles.control}>
      <button
        type="button"
        className={`${styles.arrow} ${Number(myVote) === 1 ? styles.activeUp : ''}`}
        aria-label={upvoteLabel}
        aria-pressed={Number(myVote) === 1}
        disabled={disabled}
        onClick={() => onVote?.(nextValue(1))}
      >
        ▲
      </button>
      <span className={styles.score}>{Number.isFinite(Number(score)) ? Number(score) : 0}</span>
      <button
        type="button"
        className={`${styles.arrow} ${Number(myVote) === -1 ? styles.activeDown : ''}`}
        aria-label={downvoteLabel}
        aria-pressed={Number(myVote) === -1}
        disabled={disabled}
        onClick={() => onVote?.(nextValue(-1))}
      >
        ▼
      </button>
    </div>
  )
}
