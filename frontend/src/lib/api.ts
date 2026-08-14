import axios from 'axios'

const baseURL = import.meta.env.VITE_API_URL ?? 'http://localhost:8000'

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
