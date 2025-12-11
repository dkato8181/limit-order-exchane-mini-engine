import axios from 'axios'

const api = axios.create({
  baseURL: 'http://localhost:8000',
  withCredentials: true,
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json'
  }
})

// Get CSRF token before any request
api.interceptors.request.use(async (config) => {
  // Only get CSRF token for state-changing methods
  if (['post', 'put', 'patch', 'delete'].includes(config.method)) {
    await axios.get('http://localhost:8000/sanctum/csrf-cookie', {
      withCredentials: true
    })
  }
  return config
})

export default api