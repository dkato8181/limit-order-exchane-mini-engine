import axios from 'axios'

const api = axios.create({
  baseURL: 'http://localhost:8000',
  withCredentials: true,
  withXSRFToken: true,
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json'
  }
})

// Get CSRF token before any request
api.interceptors.request.use(async (config) => {
  if (config.url?.includes('/login') || config.url?.includes('/register')) {
    return config
  }
  // Only get CSRF token for state-changing methods
  if (['post', 'put', 'patch', 'delete'].includes(config.method)) {
    await axios.get('http://localhost:8000/sanctum/csrf-cookie', {
      withCredentials: true
    })
  }
  return config
})

export default api