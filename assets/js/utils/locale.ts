export const getCurrentLanguage = () => document.documentElement.getAttribute('lang') || 'nl';
export const getCurrentLocale = () => (getCurrentLanguage() === 'nl' ? 'nl-NL' : 'en-GB');
