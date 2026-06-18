# Frontend Integration Guide (API V1)

This project uses **cookie-based session authentication** (Laravel Sanctum) for the API. This means you do **not** handle Bearer tokens manually. The browser handles the cookies automatically.

## 1. Important Client Configuration
You **must** configure your HTTP client (Axios/Fetch) to include credentials (cookies) in every request.

### Axios Example
```javascript
import axios from 'axios';

const api = axios.create({
    baseURL: 'https://ai.eduvoo.com',
    withCredentials: true, // <--- CRITICAL: Sends/Receives cookies
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    }
});
```

## 2. Authentication Flow

### Step A: CSRF Initialization (Once per app load)
Before making any POST/PUT/DELETE requests (including Login), you must call this endpoint. It sets the `XSRF-TOKEN` cookie which Axios automatically reads and attaches to headers.

```javascript
// Call this first!
await api.get('/sanctum/csrf-cookie');
```

### Step B: Login
Perform a standard POST request. **No token is returned in the body.** Instead, a `laravel_session` cookie is set by the server.

```javascript
await api.post('/api/v1/login', {
    email: 'user@example.com',
    password: 'password'
});
// If successful, the user is now logged in. Proceed to other calls.
```

### Step C: Logout
```javascript
await api.post('/api/v1/logout');
```

## 3. Using the Tools (Async Jobs)
Tools like Presentation, Animation, and Audio run in the background.

### Flow:
1.  **Create Session** (Context for the tool)
    ```javascript
    const session = await api.post('/api/v1/sessions', { title: 'My Project' });
    const sessionId = session.data.id;
    ```
2.  **Start Tool Job**
    ```javascript
    const response = await api.post('/api/v1/tools/presentation', {
        session_id: sessionId,
        topic: 'AI in 2024',
        style: 'Modern'
    });
    
    const jobId = response.data.job_id;
    ```
3.  **Poll for Status**
    Check the status every 3-5 seconds until it is `succeeded`.
    ```javascript
    const checkStatus = async (id) => {
        const { data } = await api.get(`/api/v1/jobs/${id}`);
        
        if (data.status === 'succeeded') {
            console.log("Result:", data.results); // Contains PDF path, etc.
        } else if (data.status === 'failed') {
            console.error("Error:", data.error_message);
        } else {
            setTimeout(() => checkStatus(id), 3000); // Retry
        }
    }
    checkStatus(jobId);
    ```

## 5. Downloads (On-Demand)
These endpoints trigger the generation of the file if it doesn't verify exist.

| Method | Endpoint | Description |
| :--- | :--- | :--- |
| `GET` | `/api/v1/downloads/presentation/{presentation_id}/pdf` | Download PDF |
| `GET` | `/api/v1/downloads/presentation/{presentation_id}/ppt` | Download PowerPoint |
| `GET` | `/api/v1/downloads/mindmap/{job_id}/png` | Download MindMap PNG |

## 4. Endpoints Overview

| Method | Endpoint | Description |
| :--- | :--- | :--- |
| `POST` | `/api/v1/login` | Login (Start Session) |
| `POST` | `/api/v1/logout` | Logout |
| `GET` | `/api/v1/user` | Get current user info |
| `PUT` | `/api/v1/user` | Update Name/WhatsApp/Country |
| `GET` | `/api/v1/sessions` | List Chat Sessions |
| `POST` | `/api/v1/tools/presentation` | Start Presentation Job |
| `POST` | `/api/v1/tools/mindmap` | Start Mind Map Job |
| `POST` | `/api/v1/tools/animation` | Start Animation Job |
| `POST` | `/api/v1/tools/audio` | Start Audio Job |
| `GET` | `/api/v1/jobs/{id}` | Check Job Status |

