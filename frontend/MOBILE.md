# Slam In mobile

Il frontend web è anche la sorgente delle app Capacitor Android e iOS. Le app
usano lo stesso layout React e comunicano con Laravel tramite Bearer token.

## Requisiti

- Node.js 22 o successivo
- Android Studio 2025.2.1 o successivo
- Android SDK 36 e JDK 21
- Per iOS: macOS con Xcode 26 o successivo

Su Windows, se `java` non è nel `PATH`, Android Studio include un JDK:

```powershell
$env:JAVA_HOME = 'C:\Program Files\Android\Android Studio\jbr'
$env:ANDROID_HOME = "$env:LOCALAPPDATA\Android\Sdk"
```

## Configurazione

- App ID Android/iOS: `com.slamin.app`
- URL API mobile: `frontend/.env.mobile`
- Schema link interno: `slamin://app/<percorso>`
- Sorgente icona e splash: `frontend/assets/logo.svg`

La modalità mobile deve usare un backend HTTPS raggiungibile dal dispositivo.
Non usare indirizzi Herd, `localhost` o `127.0.0.1` in `.env.mobile`.

## Comandi

Eseguire dalla cartella `frontend`:

```bash
npm install
npm run build:mobile
npm run mobile:sync
```

Rigenerare icone e splash screen dopo aver modificato `assets/logo.svg`:

```bash
npm run mobile:assets
```

Aprire Android Studio:

```bash
npm run mobile:android
```

Creare un APK debug:

```bash
npm run mobile:android:build
```

L’APK viene prodotto in:

```text
frontend/android/app/build/outputs/apk/debug/app-debug.apk
```

Installazione diretta su un dispositivo collegato con debug USB:

```bash
adb install -r android/app/build/outputs/apk/debug/app-debug.apk
```

## iOS

Il progetto iOS è già predisposto, ma compilazione e firma richiedono un Mac.
Sul Mac:

```bash
npm ci
npm run mobile:sync
npm run mobile:ios
```

In Xcode selezionare il team Apple, impostare certificati e provisioning, quindi
provare prima su un dispositivo reale. L’App Store richiederà inoltre icone,
scheda privacy, screenshot e account Apple Developer.

## Flusso di sviluppo

1. Modificare il frontend React normalmente.
2. Verificare la versione web con `npm run build`.
3. Eseguire `npm run mobile:sync` per copiare la build nelle piattaforme native.
4. Compilare o avviare dall’IDE nativo.

Le directory `android/app/src/main/assets/public` e `ios/App/App/public` sono
generate da Capacitor e non vanno modificate o versionate manualmente.

## Controlli minimi prima di una release

- avvio e splash screen
- login, logout e persistenza della sessione
- cambio lingua
- navigazione e pulsante indietro Android
- upload di foto e video
- riproduzione dei media
- chat e notifiche in tempo reale
- apertura di `slamin://app/dashboard`
- layout portrait e landscape su telefoni reali
