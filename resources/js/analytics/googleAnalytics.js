const consentStorageKey = 'blipblap_cookie_consent';
const scriptId = 'google-analytics-gtag';

let booted = false;
let measurementId = null;

const getStoredConsent = () => {
  try {
    const storedConsent = window.localStorage.getItem(consentStorageKey);

    if (!storedConsent) {
      return null;
    }

    return JSON.parse(storedConsent)?.value || null;
  } catch {
    return null;
  }
};

const hasAnalyticsConsent = () => getStoredConsent() === 'all';

const currentPath = () => `${window.location.pathname}${window.location.search}${window.location.hash}`;

const loadGoogleTagScript = () => {
  if (document.getElementById(scriptId)) {
    return;
  }

  const script = document.createElement('script');
  script.id = scriptId;
  script.async = true;
  script.src = `https://www.googletagmanager.com/gtag/js?id=${encodeURIComponent(measurementId)}`;
  document.head.appendChild(script);
};

const bootGoogleAnalytics = () => {
  if (booted || !measurementId) {
    return;
  }

  booted = true;
  window.dataLayer = window.dataLayer || [];
  window.gtag = function gtag() {
    window.dataLayer.push(arguments);
  };
  window.gtag('js', new Date());
  loadGoogleTagScript();
};

const trackPageView = () => {
  if (!measurementId || !window.gtag) {
    return;
  }

  window.gtag('config', measurementId, {
    page_path: currentPath(),
  });
};

const startAnalytics = () => {
  if (!hasAnalyticsConsent()) {
    return;
  }

  bootGoogleAnalytics();
  trackPageView();
};

export const initGoogleAnalytics = ({ measurementId: configuredMeasurementId, router: inertiaRouter }) => {
  if (typeof window === 'undefined' || typeof document === 'undefined' || !configuredMeasurementId) {
    return;
  }

  measurementId = configuredMeasurementId;
  startAnalytics();

  window.addEventListener('blipblap:cookie-consent', (event) => {
    if (event.detail?.value === 'all') {
      startAnalytics();
    }
  });

  inertiaRouter.on('navigate', () => {
    startAnalytics();
  });
};
