<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Weather for Farmers - AGROMATI</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/weather.css">
</head>
<body>
    <div class="container">
        <header>
            <a href="index.php" class="logo">
                <img src="https://placehold.co/200x60/2A7F62/FFFFFF/png?text=AGROMATI" alt="AGROMATI Logo" class="logo-img">
            </a>
            <h1>Agricultural Weather Dashboard</h1>
            <div class="location-selector">
                <i class="fas fa-map-marker-alt"></i>
                <select id="district-select">
                    <option value="">Select a District</option>
                </select>
                <i class="fas fa-chevron-down"></i>
            </div>
        </header>
        
        <div class="dashboard">
            <div class="card weather-card" id="weather-card">
                <div class="loading">
                    <div class="loading-spinner"></div>
                    <div>Select a district to view weather data</div>
                </div>
            </div>
            
            <div class="card">
                <div class="section-title">
                    <i class="fas fa-calendar-day"></i>
                    <span>5-Day Forecast</span>
                </div>
                
                <div class="forecast-cards" id="forecast-cards">
                    <div class="loading">Select a district to view forecast</div>
                </div>
            </div>
            
            <div class="card">
                <div class="section-title">
                    <i class="fas fa-seedling"></i>
                    <span>Farming Recommendations</span>
                </div>
                
                <div class="info-cards" id="recommendations">
                    <div class="loading">Select a district to get recommendations</div>
                </div>
            </div>
            
            <div class="card">
                <div class="section-title">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Seasonal Advice</span>
                </div>
                
                <div class="info-cards">
                    <div class="info-card">
                        <div class="info-icon">
                            <i class="fas fa-rice"></i>
                        </div>
                        <div class="info-title">Rice Cultivation</div>
                        <div class="info-text">Good time for transplanting Aman rice. Adequate rainfall expected.</div>
                    </div>
                    
                    <div class="info-card">
                        <div class="info-icon">
                            <i class="fas fa-carrot"></i>
                        </div>
                        <div class="info-title">Vegetables</div>
                        <div class="info-text">Suitable conditions for planting leafy vegetables and gourds.</div>
                    </div>
                </div>
            </div>
        </div>
        
        <footer>
            <p>AGROMATI - Smart Farming Analytics Platform</p>
            <p class="api-info">Weather data powered by OpenWeatherMap API</p>
        </footer>
    </div>

    <script>
        // API key for OpenWeatherMap - already integrated
        const API_KEY = '0a2fe24ce1906aeadf07e8d42c327e15';
        
        // List of all 64 districts in Bangladesh
        const districts = [
            "Bagerhat", "Bandarban", "Barguna", "Barisal", "Bhola", "Bogra", "Brahmanbaria", "Chandpur",
            "Chittagong", "Chuadanga", "Comilla", "Cox's Bazar", "Dhaka", "Dinajpur", "Faridpur", "Feni",
            "Gaibandha", "Gazipur", "Gopalganj", "Habiganj", "Jamalpur", "Jessore", "Jhalokati", "Jhenaidah",
            "Joypurhat", "Khagrachari", "Khulna", "Kishoreganj", "Kurigram", "Kushtia", "Lakshmipur", "Lalmonirhat",
            "Madaripur", "Magura", "Manikganj", "Meherpur", "Moulvibazar", "Munshiganj", "Mymensingh", "Naogaon",
            "Narail", "Narayanganj", "Narsingdi", "Natore", "Nawabganj", "Netrakona", "Nilphamari", "Noakhali",
            "Pabna", "Panchagarh", "Patuakhali", "Pirojpur", "Rajbari", "Rajshahi", "Rangamati", "Rangpur",
            "Satkhira", "Shariatpur", "Sherpur", "Sirajganj", "Sunamganj", "Sylhet", "Tangail", "Thakurgaon"
        ];
        
        // Populate district dropdown
        document.addEventListener('DOMContentLoaded', function() {
            const districtSelect = document.getElementById('district-select');
            
            // Sort districts alphabetically
            districts.sort().forEach(district => {
                const option = document.createElement('option');
                option.value = district;
                option.textContent = district;
                districtSelect.appendChild(option);
            });
            
            // Set default selection to Dhaka
            districtSelect.value = "Dhaka";
            updateWeatherData("Dhaka");
        });
        
        // Location change handler
        document.getElementById('district-select').addEventListener('change', function() {
            if (this.value) {
                updateWeatherData(this.value);
            }
        });
        
        // Update weather data
        async function updateWeatherData(location) {
            // Show loading state
            document.getElementById('weather-card').innerHTML = `
                <div class="loading">
                    <div class="loading-spinner"></div>
                    <div>Loading weather data for ${location}...</div>
                </div>
            `;
            
            document.getElementById('forecast-cards').innerHTML = `
                <div class="loading">Loading forecast...</div>
            `;
            
            document.getElementById('recommendations').innerHTML = `
                <div class="loading">Loading recommendations...</div>
            `;
            
            try {
                // Fetch current weather
                const currentWeatherUrl = `https://api.openweathermap.org/data/2.5/weather?q=${location},BD&units=metric&appid=${API_KEY}`;
                const currentResponse = await fetch(currentWeatherUrl);
                
                if (!currentResponse.ok) {
                    throw new Error(`Weather API error: ${currentResponse.status}`);
                }
                
                const currentData = await currentResponse.json();
                
                // Fetch 5-day forecast
                const forecastUrl = `https://api.openweathermap.org/data/2.5/forecast?q=${location},BD&units=metric&appid=${API_KEY}`;
                const forecastResponse = await fetch(forecastUrl);
                
                if (!forecastResponse.ok) {
                    throw new Error(`Forecast API error: ${forecastResponse.status}`);
                }
                
                const forecastData = await forecastResponse.json();
                
                // Update UI with weather data
                displayCurrentWeather(currentData);
                displayForecast(forecastData);
                updateFarmingRecommendations(currentData);
                
            } catch (error) {
                console.error('Error fetching weather data:', error);
                alert('Failed to load weather data. Please check your internet connection and try again.');
                
                // Show demo data as fallback
                displayDemoData(location);
            }
        }
        
        // Display current weather data
        function displayCurrentWeather(data) {
            const weatherIcon = getWeatherIcon(data.weather[0].icon);
            
            document.getElementById('weather-card').innerHTML = `
                <div class="current-weather">
                    <div class="weather-icon">
                        <i class="${weatherIcon}"></i>
                    </div>
                    <div class="current-temp">${Math.round(data.main.temp)}°C</div>
                    <div class="weather-desc">${capitalizeFirstLetter(data.weather[0].description)}</div>
                    <div class="location">${data.name}, Bangladesh</div>
                </div>
                
                <div class="weather-details">
                    <div class="detail-item">
                        <div class="detail-icon">
                            <i class="fas fa-tint"></i>
                        </div>
                        <div class="detail-text">
                            <div class="detail-value">${data.main.humidity}%</div>
                            <div class="detail-label">Humidity</div>
                        </div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-icon">
                            <i class="fas fa-wind"></i>
                        </div>
                        <div class="detail-text">
                            <div class="detail-value">${data.wind.speed} m/s</div>
                            <div class="detail-label">Wind Speed</div>
                        </div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-icon">
                            <i class="fas fa-compress-alt"></i>
                        </div>
                        <div class="detail-text">
                            <div class="detail-value">${data.main.pressure} hPa</div>
                            <div class="detail-label">Pressure</div>
                        </div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-icon">
                            <i class="fas fa-eye"></i>
                        </div>
                        <div class="detail-text">
                            <div class="detail-value">${data.visibility / 1000} km</div>
                            <div class="detail-label">Visibility</div>
                        </div>
                    </div>
                </div>
            `;
        }
        
        // Display 5-day forecast
        function displayForecast(data) {
            // Process forecast data to get one entry per day
            const dailyForecast = {};
            data.list.forEach(item => {
                const date = new Date(item.dt * 1000);
                const dateString = date.toDateString();
                
                if (!dailyForecast[dateString]) {
                    dailyForecast[dateString] = {
                        date: date,
                        temps: [],
                        icons: [],
                        descriptions: []
                    };
                }
                
                dailyForecast[dateString].temps.push(item.main.temp);
                dailyForecast[dateString].icons.push(item.weather[0].icon);
                dailyForecast[dateString].descriptions.push(item.weather[0].description);
            });
            
            // Get the next 5 days
            const forecastDays = Object.values(dailyForecast).slice(0, 5);
            
            // Generate forecast cards
            let forecastHtml = '';
            forecastDays.forEach(day => {
                const avgTemp = Math.round(day.temps.reduce((a, b) => a + b, 0) / day.temps.length);
                const mostCommonIcon = getMostCommonIcon(day.icons);
                const dayName = day.date.toLocaleDateString('en-US', { weekday: 'short' });
                
                forecastHtml += `
                    <div class="forecast-card">
                        <div class="forecast-day">${dayName}</div>
                        <div class="forecast-icon"><i class="${getWeatherIcon(mostCommonIcon)}"></i></div>
                        <div class="forecast-temp">${avgTemp}°C</div>
                    </div>
                `;
            });
            
            document.getElementById('forecast-cards').innerHTML = forecastHtml;
        }
        
        // Update farming recommendations based on weather
        function updateFarmingRecommendations(data) {
            let irrigationText, sprayingText, protectionText;
            
            // Simple logic for recommendations based on weather
            if (data.main.humidity < 60) {
                irrigationText = "Increased irrigation recommended. Soil moisture is low.";
            } else if (data.main.humidity > 80) {
                irrigationText = "Reduce irrigation. High humidity may cause waterlogging.";
            } else {
                irrigationText = "Moderate irrigation recommended. Soil moisture is at adequate levels.";
            }
            
            if (data.wind.speed > 5) {
                sprayingText = "Avoid spraying today. Wind speed is too high for effective application.";
            } else {
                sprayingText = "Good conditions for spraying. Wind speed is acceptable.";
            }
            
            if (data.main.temp > 35) {
                protectionText = "High temperatures expected. Provide shade for sensitive crops and ensure adequate watering.";
            } else if (data.weather[0].main === "Rain") {
                protectionText = "Rain expected. Ensure proper drainage and protect crops from heavy rainfall.";
            } else {
                protectionText = "No extreme weather expected. Normal crop activities can continue.";
            }
            
            // Update the recommendation cards
            document.getElementById('recommendations').innerHTML = `
                <div class="info-card">
                    <div class="info-icon">
                        <i class="fas fa-tint"></i>
                    </div>
                    <div class="info-title">Irrigation</div>
                    <div class="info-text">${irrigationText}</div>
                </div>
                
                <div class="info-card">
                    <div class="info-icon">
                        <i class="fas fa-spray-can"></i>
                    </div>
                    <div class="info-title">Spraying</div>
                    <div class="info-text">${sprayingText}</div>
                </div>
                
                <div class="info-card">
                    <div class="info-icon">
                        <i class="fas fa-cloud-sun"></i>
                    </div>
                    <div class="info-title">Crop Protection</div>
                    <div class="info-text">${protectionText}</div>
                </div>
            `;
        }
        
        // Helper function to get weather icon class
        function getWeatherIcon(iconCode) {
            const iconMap = {
                '01d': 'fas fa-sun',
                '01n': 'fas fa-moon',
                '02d': 'fas fa-cloud-sun',
                '02n': 'fas fa-cloud-moon',
                '03d': 'fas fa-cloud',
                '03n': 'fas fa-cloud',
                '04d': 'fas fa-cloud',
                '04n': 'fas fa-cloud',
                '09d': 'fas fa-cloud-showers-heavy',
                '09n': 'fas fa-cloud-showers-heavy',
                '10d': 'fas fa-cloud-sun-rain',
                '10n': 'fas fa-cloud-moon-rain',
                '11d': 'fas fa-bolt',
                '11n': 'fas fa-bolt',
                '13d': 'fas fa-snowflake',
                '13n': 'fas fa-snowflake',
                '50d': 'fas fa-smog',
                '50n': 'fas fa-smog'
            };
            
            return iconMap[iconCode] || 'fas fa-cloud';
        }
        
        // Helper function to get most common icon from array
        function getMostCommonIcon(icons) {
            const counts = {};
            icons.forEach(icon => {
                counts[icon] = (counts[icon] || 0) + 1;
            });
            
            return Object.keys(counts).reduce((a, b) => counts[a] > counts[b] ? a : b);
        }
        
        // Helper function to capitalize first letter
        function capitalizeFirstLetter(string) {
            return string.charAt(0).toUpperCase() + string.slice(1);
        }
        
        // Fallback demo data display
        function displayDemoData(location) {
            document.getElementById('weather-card').innerHTML = `
                <div class="current-weather">
                    <div class="weather-icon">
                        <i class="fas fa-sun"></i>
                    </div>
                    <div class="current-temp">30°C</div>
                    <div class="weather-desc">Sunny</div>
                    <div class="location">${location}, Bangladesh</div>
                </div>
                
                <div class="weather-details">
                    <div class="detail-item">
                        <div class="detail-icon">
                            <i class="fas fa-tint"></i>
                        </div>
                        <div class="detail-text">
                            <div class="detail-value">65%</div>
                            <div class="detail-label">Humidity</div>
                        </div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-icon">
                            <i class="fas fa-wind"></i>
                        </div>
                        <div class="detail-text">
                            <div class="detail-value">12 km/h</div>
                            <div class="detail-label">Wind Speed</div>
                        </div>
                    </div>
                    
                    <div class='detail-item'>
                        <div class='detail-icon'>
                            <i class='fas fa-compress-alt'></i>
                        </div>
                        <div class='detail-text'>
                            <div class='detail-value'>1013 hPa</div>
                            <div class='detail-label'>Pressure</div>
                        </div>
                    </div>
                    
                    <div class='detail-item'>
                        <div class='detail-icon'>
                            <i class='fas fa-eye'></i>
                        </div>
                        <div class='detail-text'>
                            <div class='detail-value'>10 km</div>
                            <div class='detail-label'>Visibility</div>
                        </div>
                    </div>
                </div>
            `;
            
            document.getElementById('forecast-cards').innerHTML = `
                <div class="forecast-card">
                    <div class="forecast-day">Today</div>
                    <div class="forecast-icon"><i class="fas fa-sun"></i></div>
                    <div class="forecast-temp">30°C</div>
                </div>
                
                <div class="forecast-card">
                    <div class="forecast-day">Tue</div>
                    <div class="forecast-icon"><i class="fas fa-cloud-sun"></i></div>
                    <div class="forecast-temp">29°C</div>
                </div>
                
                <div class="forecast-card">
                    <div class="forecast-day">Wed</div>
                    <div class="forecast-icon"><i class="fas fa-cloud-rain"></i></div>
                    <div class="forecast-temp">27°C</div>
                </div>
                
                <div class="forecast-card">
                    <div class="forecast-day">Thu</div>
                    <div class="forecast-icon"><i class="fas fa-cloud-showers-heavy"></i></div>
                    <div class="forecast-temp">26°C</div>
                </div>
                
                <div class="forecast-card">
                    <div class="forecast-day">Fri</div>
                    <div class="forecast-icon"><i class="fas fa-sun"></i></div>
                    <div class="forecast-temp">28°C</div>
                </div>
            `;
            
            document.getElementById('recommendations').innerHTML = `
                <div class="info-card">
                    <div class="info-icon">
                        <i class="fas fa-tint"></i>
                    </div>
                    <div class="info-title">Irrigation</div>
                    <div class="info-text">Moderate irrigation recommended. Soil moisture is at adequate levels.</div>
                </div>
                
                <div class="info-card">
                    <div class="info-icon">
                        <i class="fas fa-spray-can"></i>
                    </div>
                    <div class="info-title">Spraying</div>
                    <div class="info-text">Good conditions for spraying. Wind speed is low today.</div>
                </div>
                
                <div class="info-card">
                    <div class="info-icon">
                        <i class="fas fa-cloud-sun"></i>
                    </div>
                    <div class="info-title">Crop Protection</div>
                    <div class="info-text">No extreme weather expected. Normal crop activities can continue.</div>
                </div>
            `;
        }
    </script>
</body>
</html>