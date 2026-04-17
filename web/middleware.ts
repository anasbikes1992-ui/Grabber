import createMiddleware from 'next-intl/middleware';
import {routing} from './i18n/routing';

export default createMiddleware(routing);

export const config = {
  matcher: ['/', '/(en|si|ta|ar|hi|zh|ja|fr)/:path*']
};
