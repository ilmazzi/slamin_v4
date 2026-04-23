import {useEffect, useMemo, useRef, useState} from 'react'
import styles from './PersonalEventsCalendar.module.css'
import { useI18n } from '../app/hooks/useI18n'
import { VisibilityOffIcon, VisibilityOnIcon } from './icons/ActionIcons'
import { useAuth } from '../context/AuthContext.jsx'
import { Link } from 'react-router-dom'
import { expandEventOccurrencesForCalendar, fetchAllMyEvents } from '../app/api/eventMappers.js'




export default function PersonalEventsCalendar({ tone = 'default',showVisibilityToggle = true, }) {

    const { t, locale } = useI18n()
    const { token } = useAuth()

    const MONTHS = [t('common.months.january'), t('common.months.february'), t('common.months.march'), t('common.months.april'), t('common.months.may'), t('common.months.june'), t('common.months.july'), t('common.months.august'), t('common.months.september'), t('common.months.october'), t('common.months.november'), t('common.months.december')]

    const [isHidden, setIsHidden] = useState(false)
    const [selectedMonth, setSelectedMonth] = useState(new Date().getMonth())
    const [selectedYear, setSelectedYear] = useState(new Date().getFullYear())
    const [isMonthSelectOpen, setIsMonthSelectOpen] = useState(false)
    const [events, setEvents] = useState([])
    const [isLoadingEvents, setIsLoadingEvents] = useState(false)
    const [selectedDayKey, setSelectedDayKey] = useState(null)
    const dayDetailsRef = useRef(null)


    const calendarDays = useMemo(() => {
        const firstDayOfMonth = new Date(selectedYear, selectedMonth, 1)
        const mondayIndex = firstDayOfMonth.getDay() === 0 ? 6 : firstDayOfMonth.getDay() - 1
        const gridStartDate = new Date(selectedYear, selectedMonth, 1 - mondayIndex)
        return Array.from({ length: 42 }, (_, index) => {
            const date = new Date(gridStartDate)
            date.setDate(date.getDate() + index)
            const isCurrentMonth = date.getMonth() === selectedMonth
            const isToday = date.toDateString() === new Date().toDateString()
          const dayNumber = date.getDate()
            return {
                date,
                dayNumber,
                isCurrentMonth,
                isToday,
            }
        })
    }, [selectedYear, selectedMonth])

    const weekDaysShort = [t('common.weekdays_short.mon'), t('common.weekdays_short.tue'), t('common.weekdays_short.wed'), t('common.weekdays_short.thu'), t('common.weekdays_short.fri'), t('common.weekdays_short.sat'), t('common.weekdays_short.sun')]
    const weekDaysLong = [t('common.weekdays_long.monday'), t('common.weekdays_long.tuesday'), t('common.weekdays_long.wednesday'), t('common.weekdays_long.thursday'), t('common.weekdays_long.friday'), t('common.weekdays_long.saturday'), t('common.weekdays_long.sunday')]
    const toDayKey = (date) => `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`

    useEffect(() => {
      if (!token) {
        setEvents([])
        setSelectedDayKey(null)
        setIsLoadingEvents(false)
        return
      }

      const controller = new AbortController()
      setIsLoadingEvents(true)

      async function loadCalendarEvents() {
        try {
          const raw = await fetchAllMyEvents({ token, signal: controller.signal })
          const rows = raw.flatMap((e) => expandEventOccurrencesForCalendar(e, { relations: ['owned'] }))
          setEvents(rows)
        } catch (error) {
          if (error?.name !== 'AbortError') {
            console.error('Unable to load calendar events:', error)
            setEvents([])
          }
        } finally {
          if (!controller.signal.aborted) {
            setIsLoadingEvents(false)
          }
        }
      }

      loadCalendarEvents()

      return () => controller.abort()
    }, [token])

    useEffect(() => {
      setSelectedDayKey(null)
    }, [selectedMonth, selectedYear])

    const eventsByDay = useMemo(() => {
      const byDay = new Map()

      for (const event of events) {
        if (!event?.start_datetime) {
          continue
        }

        const startDate = new Date(event.start_datetime)
        if (Number.isNaN(startDate.getTime())) {
          continue
        }

        const dayKey = toDayKey(startDate)
        const current = byDay.get(dayKey) ?? []
        current.push({
          id: event.id,
          occurrenceId: event.occurrenceId,
          slug: event.slug,
          title: event.title || event.subtitle || t('common.events'),
          subtitle: event.subtitle || '',
          startDatetime: event.start_datetime,
          venueName: event.venue_name || '',
          city: event.city || '',
          relations: event.relations ?? [],
        })
        byDay.set(dayKey, current)
      }

      return byDay
    }, [events, t])

    const selectedDayEvents = selectedDayKey ? (eventsByDay.get(selectedDayKey) ?? []) : []
    const selectedDayDate = selectedDayKey ? new Date(`${selectedDayKey}T00:00:00`) : null
    const selectedDayTitle = selectedDayDate && !Number.isNaN(selectedDayDate.getTime())
      ? selectedDayDate.toLocaleDateString(locale || 'it-IT', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
      })
      : ''
    useEffect(() => {
      if (isHidden || isLoadingEvents || !selectedDayKey || selectedDayEvents.length === 0 || !dayDetailsRef.current) {
        return
      }

      dayDetailsRef.current.scrollIntoView({ behavior: 'smooth', block: 'nearest' })
      dayDetailsRef.current.focus({ preventScroll: true })
    }, [isHidden, isLoadingEvents, selectedDayKey, selectedDayEvents.length])

    const formatTime = (value) => {
      if (!value) {
        return ''
      }
      const date = new Date(value)
      if (Number.isNaN(date.getTime())) {
        return ''
      }
      return date.toLocaleTimeString(locale || 'it-IT', { hour: '2-digit', minute: '2-digit' })
    }
    const resolvePrimaryRelation = (relations = []) => {
      if (relations.includes('owned')) {
        return t('dashboard.calendar_relation_owned')
      }
      if (relations.includes('participating')) {
        return t('dashboard.calendar_relation_participating')
      }
      if (relations.includes('wishlist')) {
        return t('dashboard.calendar_relation_wishlist')
      }
      return ''
    }



  return (
    <div className={`${styles.calendarRoot} ${tone === 'event' ? styles.toneEvent : ''}`}>

<section className={styles.calendarHeader}>
<h2 className={styles.title}>
  <span className={styles.titleAccent}>{t('dashboard.calendar_title_accent').toLowerCase()}</span>{'  '}
  <span className={styles.titleMiddle}>{t('dashboard.calendar_title_middle').toLowerCase()}</span>{' '}
  <span className={styles.titleTail}>{t('dashboard.calendar_title_tail').toLowerCase()}</span>
</h2>

{showVisibilityToggle ? <button type="button" className={styles.hideButtonDesktop} onClick={() => setIsHidden(prev => !prev)}>
  <span className={styles.hideButtonIcon} aria-hidden="true">
    {isHidden ? <VisibilityOnIcon className={styles.hideButtonIconSvg} /> : <VisibilityOffIcon className={styles.hideButtonIconSvg} />}
  </span>
  <span className={`${styles.hideButtonLabel} typeUiSection`}>{isHidden ? t('dashboard.show') : t('dashboard.hide')}</span>
</button> : null}
</section>

{!isHidden && (
  <>
    <section className={styles.calendarPeriodRow}>
    <section className={styles.monthsRow}>

    {MONTHS.map((month, index) => (
       
       <button
       key={index}
       onClick={() => setSelectedMonth(index)}
       className={index === selectedMonth ? styles.monthButtonActive : styles.monthButton}
       type="button"
     >
       <span className={index === selectedMonth ? styles.monthLabelActive : styles.monthLabel}>
        {month}
       </span>
     </button>
        
    ))}
    </section>
    <div className={styles.yearSwitcher}>
      <button
        type="button"
        className={styles.yearNavButton}
        onClick={() => setSelectedYear((prev) => prev - 1)}
        aria-label={t('dashboard.calendar_prev_year')}
      >
        -
      </button>
      <span className={styles.yearValue}>{selectedYear}</span>
      <button
        type="button"
        className={styles.yearNavButton}
        onClick={() => setSelectedYear((prev) => prev + 1)}
        aria-label={t('dashboard.calendar_next_year')}
      >
        +
      </button>
    </div>
    </section>

    <section className={styles.daysHeader}>
        {weekDaysShort.map((day, index) => (
            <span key={day} className={styles.daysHeaderItem} title={weekDaysLong[index]}>
              {day}
            </span>
        ))}
    </section>
    <section className={styles.calendarGrid}>
        {calendarDays.map((day) => {
          const dayKey = toDayKey(day.date)
          const dayEvents = eventsByDay.get(dayKey) ?? []
          const hasEvents = dayEvents.length > 0

          return (
            <button
              key={dayKey}
              className={`${styles.calendarDay} ${day.isCurrentMonth ? '' : styles.calendarDayMuted} ${day.isToday ? styles.calendarDayToday : ''} ${hasEvents ? styles.calendarDayHasEvents : ''}`}
              type="button"
              onClick={() => setSelectedDayKey(hasEvents ? dayKey : null)}
              aria-pressed={selectedDayKey === dayKey}
            >
                <span
                  className={`${styles.calendarDayNumber} ${day.isCurrentMonth ? '' : styles.calendarDayNumberMuted} ${day.isToday ? styles.calendarDayNumberToday : ''} ${hasEvents ? styles.calendarDayNumberHasEvents : ''}`}
                >
                  {day.dayNumber}
                </span>
                {hasEvents ? (
                  <div className={styles.calendarEventsList}>
                    {dayEvents.slice(0, 2).map((event) => (
                      <div
                        key={event.occurrenceId != null ? `${event.id}-${event.occurrenceId}` : String(event.id)}
                        className={styles.calendarEventItem}
                      >
                        <span className={styles.calendarEventDot} aria-hidden="true" />
                        <span className={styles.calendarEventTitle}>{event.title}</span>
                      </div>
                    ))}
                    {dayEvents.length > 2 ? (
                      <div className={styles.calendarEventMore}>+{dayEvents.length - 2}</div>
                    ) : null}
                  </div>
                ) : null}
            </button>
          )
        })}
    </section>
    {isLoadingEvents ? <span className={styles.calendarLoading}>{t('common.loading')}</span> : null}
    {!isLoadingEvents && selectedDayEvents.length > 0 ? (
      <section className={styles.dayDetailsPanel} ref={dayDetailsRef} tabIndex={-1}>
        <div className={styles.dayDetailsHeader}>
          <h3 className={styles.dayDetailsTitle}>
            {t('dashboard.calendar_day_details_title')} {selectedDayTitle}
          </h3>
          <button
            type="button"
            className={styles.dayDetailsClose}
            onClick={() => setSelectedDayKey(null)}
          >
            {t('common.close')}
          </button>
        </div>
        <div className={styles.dayDetailsList}>
          {selectedDayEvents.map((event) => (
            <article
              key={
                event.occurrenceId != null
                  ? `detail-${event.id}-${event.occurrenceId}`
                  : `detail-${event.id}`
              }
              className={styles.dayDetailsItem}
            >
              <div className={styles.dayDetailsMain}>
                <span className={styles.dayDetailsDot} aria-hidden="true" />
                <div className={styles.dayDetailsText}>
                  {event.slug ? (
                    <Link className={styles.dayDetailsEventTitle} to={`/events/${encodeURIComponent(event.slug)}`}>
                      {event.title}
                    </Link>
                  ) : (
                    <strong className={styles.dayDetailsEventTitle}>{event.title}</strong>
                  )}
                  {event.subtitle ? <span className={styles.dayDetailsSubtitle}>{event.subtitle}</span> : null}
                </div>
              </div>
              <div className={styles.dayDetailsMeta}>
                {resolvePrimaryRelation(event.relations) ? (
                  <span className={styles.dayDetailsMetaItem}>{resolvePrimaryRelation(event.relations)}</span>
                ) : null}
                {formatTime(event.startDatetime) ? (
                  <span className={styles.dayDetailsMetaItem}>{formatTime(event.startDatetime)}</span>
                ) : null}
                {event.venueName || event.city ? (
                  <span className={styles.dayDetailsMetaItem}>{[event.venueName, event.city].filter(Boolean).join(' - ')}</span>
                ) : null}
              </div>
            </article>
          ))}
        </div>
      </section>
    ) : null}
  </>
)}

    <section className={styles.monthSelectRow}>
      <div className={`${styles.monthSelectAndHide} ${isHidden ? styles.monthSelectAndHideHidden : ''}`}>
        {!isHidden ? (
        <button
          type="button"
          className={styles.monthSelectTrigger}
          onClick={() => setIsMonthSelectOpen((open) => !open)}
          aria-expanded={isMonthSelectOpen}
          aria-haspopup="listbox"
        >
          <span className={styles.monthSelectText}>{MONTHS[selectedMonth]}</span>
          <span className={`${styles.monthSelectChevron} ${isMonthSelectOpen ? styles.monthSelectChevronOpen : ''}`} aria-hidden="true">⌄</span>
        </button>
        ) : null}
        {showVisibilityToggle ? <button type="button" className={styles.hideButtonMobile} onClick={() => setIsHidden(prev => !prev)}>
          <span className={styles.hideButtonIconMobile} aria-hidden="true">
            {isHidden ? <VisibilityOnIcon className={styles.hideButtonIconSvgMobile} /> : <VisibilityOffIcon className={styles.hideButtonIconSvgMobile} />}
          </span>
          <span className={`${styles.hideButtonLabelMobile} typeUiSection`}>{isHidden ? t('dashboard.show') : t('dashboard.hide')}</span>
        </button> : null}
      </div>
      {!isHidden && isMonthSelectOpen ? (
        <div className={styles.monthSelectMenu} role="listbox" aria-label="Selezione mese">
          {MONTHS.map((month, index) => (
            <button
              key={`mobile-${index}`}
              type="button"
              className={index === selectedMonth ? styles.monthSelectOptionActive : styles.monthSelectOption}
              onClick={() => {
                setSelectedMonth(index)
                setIsMonthSelectOpen(false)
              }}
            >
              {month}
            </button>
          ))}
        </div>
      ) : null}
    </section>
    </div>
  )
}