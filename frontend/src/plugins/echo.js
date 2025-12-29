import { configureEcho } from '@laravel/echo-vue';
import Pusher from 'pusher-js';
import axios from 'axios';

window.Pusher = Pusher;
Pusher.logToConsole = true;

axios.defaults.baseURL = import.meta.env.VITE_API_BASE_URL;
axios.defaults.withCredentials = true;
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

const echo = configureEcho({
    broadcaster: "pusher",
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
    forceTLS: true,
    enabledTransports: ["ws", "wss"],
    //authEndpoint: `${import.meta.env.VITE_API_BASE_URL}/broadcasting/auth`,
    authorizer: (channel) => {
        return {
            authorize: (socketId, callback) => {
                axios.post('/broadcasting/auth', {
                    socket_id: socketId,
                    channel_name: channel.name
                })
                .then(response => {
                    console.log('✅ Channel authorized:', channel.name);
                    callback(null, response.data);
                })
                .catch(error => {
                    console.error('❌ Authorization failed:', {
                        status: error.response?.status,
                        statusText: error.response?.statusText,
                        data: error.response?.data,
                        channel: channel.name
                    });
                    callback(error);
                });
            }
        };
    }
});

export default echo;