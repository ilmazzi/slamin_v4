import HeaderAuthActions from './HeaderAuthActions.jsx'
import HeaderGuestActions from './HeaderGuestActions.jsx'
import styles from './Header.module.css'

export default function HeaderActions(props) {
  const { variant = 'guest', t } = props

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
          menuAriaLabel={t('header.menu_aria')}
          languageAriaLabel={t('header.language_aria')}
          fallbackUserName={t('home.dashboard.profile_fallback')}
          dashboardLabel={t('header.dashboard')}
          showAdmin={props.showAdmin}
          adminHref={props.adminHref}
          adminLabel={props.adminLabel || t('header.admin')}
          adminMenuLabel={t('header.profile_menu.admin')}
          quickActionsTitle={t('header.quick_actions.title')}
          writePoemLabel={t('header.quick_actions.write_poem')}
          createEventLabel={t('header.quick_actions.create_event')}
          uploadVideoLabel={t('header.quick_actions.upload_video')}
          publishArticleLabel={t('header.quick_actions.publish_article')}
          myProfileLabel={t('header.profile_menu.profile')}
          editProfileLabel={t('header.profile_menu.edit')}
          chatLabel={t('header.profile_menu.chat')}
          logoutLabel={t('header.profile_menu.logout')}
          createGroupLabel={t('header.quick_actions.create_group')}
        />
      ) : (
        <HeaderGuestActions
          locale={props.locale}
          availableLocales={props.availableLocales}
          loginHref={props.loginHref}
          registerHref={props.registerHref}
          onLanguageChange={props.onLanguageChange}
          onMenuClick={props.onMenuClick}
          menuAriaLabel={t('header.menu_aria')}
          t={t}
        />
      )}
    </div>
  )
}
