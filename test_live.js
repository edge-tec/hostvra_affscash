const https = require('https');
const querystring = require('querystring');

const loginData = querystring.stringify({
    email: 'affiliate@example.com', // I need the actual affiliate credentials, or I can just test with whatever. Wait, I don't know the password!
});
