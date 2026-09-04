import HeaderAuthActions from './HeaderAuthActions.jsx'
import HeaderGuestActions from './HeaderGuestActions.jsx'
import styles from './Header.module.css'

export default function HeaderActions(props) {
  const { variant = 'guest' } = props

  return (
    <div className={styles.actions}>
      {variant === 'auth' ? (
        <HeaderAuthActions
          user={props.user}
          locale={props.locale}
          availableLocales={props.availableLocales}
          onLanguageChange={props.onLanguageChange}
          onMainMenuToggle={props.onMainMenuToggle}
          onMainMenuClose={props.onMainMenuClose}
          menuAriaLabel={props.t ? props.t('header.menu_aria') : 'Menu'}
          languageAriaLabel={props.t ? props.t('header.language_aria') : 'Select language'}
          fallbackUserName={
            props.t ? props.t('home.dashboard.profile_fallback') : 'Nome Cognome'
          }
          dashboardLabel={props.t ? props.t('header.dashboard') : 'Dashboard'}
          quickActionsTitle={props.t ? props.t('header.quick_actions.title') : 'Azioni rapide'}
          writePoemLabel={props.t ? props.t('header.quick_actions.write_poem') : 'Scrivi poesia'}
          createEventLabel={props.t ? props.t('header.quick_actions.create_event') : 'Organizza evento'}
          uploadVideoLabel={props.t ? props.t('header.quick_actions.upload_video') : 'Carica video'}
          publishArticleLabel={props.t ? props.t('header.quick_actions.publish_article') : 'Pubblica articolo'}
          notificationsAriaLabel={props.t ? props.t('header.notifications_aria') : 'Notifiche'}
          myProfileLabel={props.t ? props.t('header.profile_menu.profile') : 'Il mio profilo'}
          editProfileLabel={props.t ? props.t('header.profile_menu.edit') : 'Modifica profilo'}
          logoutLabel={props.t ? props.t('header.profile_menu.logout') : 'Logout'}
        />
      ) : (
        <HeaderGuestActions
          locale={props.locale}
          availableLocales={props.availableLocales}
          loginHref={props.loginHref}
          registerHref={props.registerHref}
          onLanguageChange={props.onLanguageChange}
          onMenuClick={props.onMenuClick}
          menuAriaLabel={props.t ? props.t('header.menu_aria') : 'Menu'}
          t={props.t}
        />
      )}
    </div>
  )
}
