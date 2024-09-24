import { getDocument } from './browser';

export const getCurrentLanguage = () => getDocument().documentElement.getAttribute('lang') ?? 'nl';
export const getCurrentLocale = () => (getCurrentLanguage() === 'en' ? 'en-GB' : 'nl-NL');
