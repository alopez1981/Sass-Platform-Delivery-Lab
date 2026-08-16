import axios from 'axios'

// Empty by default: requests go to the SPA's own origin (e.g.
// http://localhost:5174/api/...), which Vite's dev server proxies
// server-side to the real backend (see vite.config.ts). From the browser's
// point of view this is same-origin — no CORS, no cross-origin cookie
// concerns. Set VITE_API_URL only if you deliberately want the browser
// itself to call a different origin directly (bypassing the proxy).
const baseURL = import.meta.env.VITE_API_URL ?? ''

export const api = axios.create({
  baseURL,
  withCredentials: true,
  withXSRFToken: true,
  headers: {
    Accept: 'application/json',
  },
})

/**
 * Sanctum's SPA auth is cookie-based: before the first stateful request
 * (login), the browser needs the XSRF-TOKEN cookie that this endpoint sets.
 * Axios then attaches it automatically as the X-XSRF-TOKEN header.
 */
export function ensureCsrfCookie() {
  return axios.get(`${baseURL}/sanctum/csrf-cookie`, { withCredentials: true })
}
