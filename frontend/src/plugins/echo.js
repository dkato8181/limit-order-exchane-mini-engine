import { configureEcho } from '@laravel/echo-vue';
import Pusher from 'pusher-js';
import api from '@/api/axios';

window.Pusher = Pusher;
Pusher.logToConsole = true;

const echo = configureEcho({
    broadcaster: "pusher",
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
    forceTLS: true,
    /* wsHost: import.meta.env.VITE_PUSHER_HOST,
    wsPort: import.meta.env.VITE_PUSHER_PORT,
    wssPort: import.meta.env.VITE_PUSHER_PORT, */
    enabledTransports: ["ws", "wss"],
    /* authEndpoint: `${import.meta.env.VITE_API_BASE_URL}/broadcasting/auth`,
    auth: {
        headers: {
            Authorization: `Bearer ${localStorage.getItem('auth_token')}`,
        },
        withCredentials: true,
    },
     authorizer: (channel, options) => {
        return {
            authorize: (socketId, callback) => {
                const token = localStorage.getItem('auth_token');
                
                console.log('Authorizing channel:', channel.name);
                console.log('Socket ID:', socketId);
                console.log('Token:', token ? 'Present' : 'Missing');
                
                api.post(`${import.meta.env.VITE_API_BASE_URL}/broadcasting/auth`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'Authorization': `Bearer ${token}`,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'include',
                    body: JSON.stringify({
                        socket_id: socketId,
                        channel_name: channel.name
                    })
                })
                .then(response => {
                    console.log('Auth response status:', response.status);
                    
                    if (!response.ok) {
                        return response.text().then(text => {
                            console.error('Auth error response:', text);
                            throw new Error(`Auth failed: ${response.status}`);
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Auth successful:', data);
                    callback(null, data);
                })
                .catch(error => {
                    console.error('Authorization error:', error);
                    callback(error);
                });
            }
        };
    } */
});

export default echo;