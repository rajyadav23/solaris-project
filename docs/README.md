# ☀️ SOLARIS — AI Energy Intelligence Platform v3.0

> Premium 3D AI-powered renewable energy dashboard with real-time monitoring,
> ML predictions, smart optimization, and an intelligent AI chatbot.

---

## 📁 Project Structure

```
solaris-project/
├── frontend/               ← Static site (deploy to Netlify/Vercel)
│   ├── index.html          ← Full 3D dashboard (Three.js + Chart.js)
│   ├── netlify.toml
│   ├── vercel.json
│   └── package.json
│
├── backend/                ← Laravel 11 REST API
│   ├── app/Http/Controllers/
│   │   ├── AuthController.php
│   │   ├── EnergyController.php
│   │   ├── PredictionController.php
│   │   └── OtherControllers.php   (Weather/Rec/Chat/Optimize)
│   ├── app/Models/
│   │   ├── EnergyReading.php
│   │   └── ChatMessage.php
│   ├── database/migrations/
│   ├── routes/api.php
│   ├── .env.example
│   └── composer.json
│
├── ml-api/                 ← FastAPI ML service
│   ├── main.py             ← Prediction endpoints
│   ├── requirements.txt
│   ├── Dockerfile
│   └── render.yaml
│
└── docs/
    └── README.md           ← This file
```

---

## 🚀 Quick Start — Frontend Only (Instant Preview)

```bash
# Option 1: Open directly in browser
open frontend/index.html

# Option 2: Local server
cd frontend
npx serve . -p 3000
# → http://localhost:3000
```

The frontend is **fully standalone** — no backend needed for the demo.  
All data is simulated client-side using realistic physics models.

---

## 🔧 Backend Setup (Laravel 11)

### Prerequisites
- PHP 8.2+
- Composer
- MySQL 8.0+

### Installation

```bash
cd backend

# 1. Install dependencies
composer install

# 2. Copy and configure .env
cp .env.example .env
# Edit .env with your DB credentials and API keys

# 3. Generate app key
php artisan key:generate

# 4. Run migrations
php artisan migrate

# 5. Install Sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate

# 6. Start dev server
php artisan serve
# → http://localhost:8000
```

### API Endpoints

| Method | Endpoint                    | Auth | Description              |
|--------|-----------------------------|------|--------------------------|
| POST   | /api/register               | ✗    | Register user            |
| POST   | /api/login                  | ✗    | Login → get token        |
| POST   | /api/logout                 | ✓    | Invalidate token         |
| GET    | /api/energy/current         | ✓    | Live KPI data            |
| GET    | /api/energy/hourly          | ✓    | 24h generation data      |
| GET    | /api/energy/daily           | ✓    | 7-day totals             |
| POST   | /api/energy/reading         | ✓    | Store sensor data        |
| GET    | /api/predictions/solar      | ✓    | 24h solar forecast       |
| GET    | /api/predictions/wind       | ✓    | 24h wind forecast        |
| GET    | /api/predictions/weekly     | ✓    | 7-day ML forecast        |
| GET    | /api/weather/current        | ✓    | Live weather             |
| GET    | /api/recommendations        | ✓    | AI recommendations       |
| GET    | /api/optimization/metrics   | ✓    | Performance metrics      |
| GET    | /api/optimization/schedule  | ✓    | Appliance schedule       |
| POST   | /api/chat                   | ✓    | Send chat message        |
| GET    | /api/chat/history           | ✓    | Chat history             |

### Authentication (Sanctum)
```bash
# Login
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"password"}'

# Use token
curl http://localhost:8000/api/energy/current \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

---

## 🤖 ML API Setup (FastAPI)

### Prerequisites
- Python 3.11+

### Installation

```bash
cd ml-api

# Create virtual environment
python -m venv venv
source venv/bin/activate   # Windows: venv\Scripts\activate

# Install dependencies
pip install -r requirements.txt

# Start server
uvicorn main:app --reload --port 8000
# → http://localhost:8000
# → Docs: http://localhost:8000/docs
```

### API Endpoints

| Method | Endpoint           | Description              |
|--------|--------------------|--------------------------|
| GET    | /                  | Status + endpoint list   |
| GET    | /health            | Health check             |
| POST   | /predict/solar     | 24h solar forecast       |
| POST   | /predict/wind      | 24h wind forecast        |
| GET    | /predict/weekly    | 7-day forecast           |
| POST   | /recommend         | AI recommendations       |

### Example Request
```bash
curl -X POST http://localhost:8000/predict/solar \
  -H "Content-Type: application/json" \
  -d '{
    "temperature": 32,
    "humidity": 64,
    "wind_speed": 19,
    "solar_irr": 780,
    "hour": 12,
    "day_of_year": 200
  }'
```

---

## 🌐 Deployment

### Frontend → Netlify (Recommended)

```bash
cd frontend

# Method 1: Drag & drop folder to netlify.com/drop
# Method 2: CLI
npm install -g netlify-cli
netlify deploy --prod --dir .
```

### Frontend → Vercel

```bash
cd frontend
npm install -g vercel
vercel --prod
```

### Backend → Railway

```bash
# Push to GitHub, then connect Railway to your repo
# Set environment variables in Railway dashboard
# Railway auto-detects Laravel and deploys
```

### ML API → Render

1. Push `ml-api/` folder to GitHub
2. Create new **Web Service** on render.com
3. Connect your repo
4. Build command: `pip install -r requirements.txt`
5. Start command: `uvicorn main:app --host 0.0.0.0 --port $PORT`

---

## 🔑 Environment Variables

### Backend (.env)
```
OPENWEATHER_API_KEY=    # Free at openweathermap.org
ML_API_URL=             # Your Render ML API URL
DB_DATABASE=solaris_db
DB_USERNAME=your_user
DB_PASSWORD=your_pass
```

### Getting Free API Keys
- **OpenWeatherMap**: https://openweathermap.org/api (free tier: 1000 calls/day)

---

## 🎨 Frontend Features

| Feature | Technology |
|---------|-----------|
| 3D Solar Panel model | Three.js r128 |
| 3D Wind Turbine model | Three.js r128 |
| Auto-rotating image slideshows | Vanilla JS crossfade |
| Area / Line charts | Chart.js 4.4 |
| Donut chart | Chart.js 4.4 |
| 3D card tilt effect | CSS perspective + JS |
| Particle background | Canvas 2D API |
| Real-time clock | JS setInterval |
| AI Chatbot UI | Vanilla JS |
| Glassmorphism | CSS backdrop-filter |
| Animations | CSS keyframes |

---

## 📊 Tech Stack Summary

| Layer | Technology |
|-------|-----------|
| Frontend | HTML5, CSS3, Vanilla JS, Three.js, Chart.js |
| Backend | Laravel 11, PHP 8.2, Laravel Sanctum |
| Database | MySQL 8.0 |
| ML API | FastAPI, Python 3.11, NumPy, Scikit-learn |
| Deployment | Netlify/Vercel + Railway + Render |

---

## 🛠️ Connecting Frontend to Backend

In `frontend/index.html`, find the API config section and update:

```javascript
const API_BASE = 'https://your-backend.railway.app/api';
const ML_BASE  = 'https://your-ml-api.onrender.com';

// After login, store token:
localStorage.setItem('solaris_token', token);

// Use in requests:
const headers = {
  'Authorization': `Bearer ${localStorage.getItem('solaris_token')}`,
  'Content-Type': 'application/json'
};
```

---

## 📜 License
MIT — Free for personal and commercial use.

---

Built with ❤️ by SOLARIS Team · AI Energy Intelligence Platform v3.0
