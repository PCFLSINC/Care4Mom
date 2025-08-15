<?php
/**
 * Care4Mom - Wellness Resources
 * Support groups, educational content, and helpful resources
 * Author: Care4Mom Development Team
 * Version: 1.0
 */

$page_title = 'Wellness Resources';
$body_class = 'module-page wellness-page';

require_once '../includes/header.php';
requireLogin();

$current_user = getCurrentUser();
?>

<div class="module-container">
    <!-- Module Header -->
    <div class="module-header">
        <div class="header-content">
            <h1 class="module-title">
                <span class="module-icon">🌟</span>
                Wellness Resources
            </h1>
            <p class="module-description">
                Find support groups, educational materials, and helpful resources for your cancer care journey.
            </p>
        </div>
    </div>
    
    <!-- Resource Categories -->
    <div class="resource-categories">
        <div class="category-grid">
            <!-- Support Groups -->
            <div class="category-card">
                <div class="category-icon">👥</div>
                <h2 class="category-title">Support Groups</h2>
                <p class="category-description">Connect with others facing similar challenges</p>
                <div class="resource-list">
                    <div class="resource-item">
                        <h3 class="resource-title">American Cancer Society Support Groups</h3>
                        <p class="resource-description">Local and online support groups for cancer patients and families</p>
                        <a href="https://www.cancer.org/support-programs-and-services/patient-lodging/hope-lodge.html" target="_blank" class="resource-link">Learn More →</a>
                    </div>
                    <div class="resource-item">
                        <h3 class="resource-title">CancerCare Support Groups</h3>
                        <p class="resource-description">Free support groups led by professional social workers</p>
                        <a href="https://www.cancercare.org" target="_blank" class="resource-link">Learn More →</a>
                    </div>
                </div>
            </div>
            
            <!-- Educational Resources -->
            <div class="category-card">
                <div class="category-icon">📚</div>
                <h2 class="category-title">Educational Resources</h2>
                <p class="category-description">Learn about your condition and treatment options</p>
                <div class="resource-list">
                    <div class="resource-item">
                        <h3 class="resource-title">National Cancer Institute</h3>
                        <p class="resource-description">Comprehensive cancer information and treatment guidelines</p>
                        <a href="https://www.cancer.gov" target="_blank" class="resource-link">Learn More →</a>
                    </div>
                    <div class="resource-item">
                        <h3 class="resource-title">Lung Cancer Foundation</h3>
                        <p class="resource-description">Specialized resources for lung cancer patients</p>
                        <a href="https://lungcancerfoundation.org" target="_blank" class="resource-link">Learn More →</a>
                    </div>
                </div>
            </div>
            
            <!-- Mental Health -->
            <div class="category-card">
                <div class="category-icon">🧠</div>
                <h2 class="category-title">Mental Health Support</h2>
                <p class="category-description">Professional counseling and mental health resources</p>
                <div class="resource-list">
                    <div class="resource-item">
                        <h3 class="resource-title">Crisis Text Line</h3>
                        <p class="resource-description">Text HOME to 741741 for 24/7 crisis support</p>
                        <a href="tel:741741" class="resource-link emergency">Text 741741 →</a>
                    </div>
                    <div class="resource-item">
                        <h3 class="resource-title">National Suicide Prevention Lifeline</h3>
                        <p class="resource-description">24/7 confidential support</p>
                        <a href="tel:988" class="resource-link emergency">Call 988 →</a>
                    </div>
                </div>
            </div>
            
            <!-- Financial Assistance -->
            <div class="category-card">
                <div class="category-icon">💰</div>
                <h2 class="category-title">Financial Assistance</h2>
                <p class="category-description">Help with treatment costs and expenses</p>
                <div class="resource-list">
                    <div class="resource-item">
                        <h3 class="resource-title">Patient Advocate Foundation</h3>
                        <p class="resource-description">Financial assistance and insurance navigation</p>
                        <a href="https://www.patientadvocate.org" target="_blank" class="resource-link">Learn More →</a>
                    </div>
                    <div class="resource-item">
                        <h3 class="resource-title">Pharmaceutical Assistance Programs</h3>
                        <p class="resource-description">Medication cost assistance from drug manufacturers</p>
                        <a href="https://www.needymeds.org" target="_blank" class="resource-link">Learn More →</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Emergency Contacts -->
    <div class="emergency-section">
        <h2 class="section-title">🚨 Emergency Contacts</h2>
        <div class="emergency-grid">
            <div class="emergency-card">
                <div class="emergency-icon">🚨</div>
                <h3 class="emergency-title">Emergency Services</h3>
                <a href="tel:911" class="emergency-button">Call 911</a>
            </div>
            
            <div class="emergency-card">
                <div class="emergency-icon">☎️</div>
                <h3 class="emergency-title">Poison Control</h3>
                <a href="tel:1-800-222-1222" class="emergency-button">1-800-222-1222</a>
            </div>
            
            <div class="emergency-card">
                <div class="emergency-icon">💬</div>
                <h3 class="emergency-title">Crisis Support</h3>
                <a href="tel:988" class="emergency-button">Call 988</a>
            </div>
        </div>
    </div>
    
    <!-- Wellness Tips -->
    <div class="wellness-tips">
        <h2 class="section-title">💡 Daily Wellness Tips</h2>
        <div class="tips-grid">
            <div class="tip-card">
                <div class="tip-icon">💧</div>
                <h3 class="tip-title">Stay Hydrated</h3>
                <p class="tip-content">Drink plenty of water throughout the day, especially during treatment.</p>
            </div>
            
            <div class="tip-card">
                <div class="tip-icon">🚶‍♀️</div>
                <h3 class="tip-title">Gentle Movement</h3>
                <p class="tip-content">Light exercise like walking can help maintain strength and mood.</p>
            </div>
            
            <div class="tip-card">
                <div class="tip-icon">😴</div>
                <h3 class="tip-title">Rest When Needed</h3>
                <p class="tip-content">Listen to your body and rest when you feel tired or overwhelmed.</p>
            </div>
            
            <div class="tip-card">
                <div class="tip-icon">🤗</div>
                <h3 class="tip-title">Stay Connected</h3>
                <p class="tip-content">Maintain relationships with family and friends for emotional support.</p>
            </div>
        </div>
    </div>
    
    <!-- Local Resources -->
    <div class="local-resources">
        <h2 class="section-title">📍 Find Local Resources</h2>
        <div class="search-section">
            <p class="search-description">Enter your zip code to find cancer centers, support groups, and services near you.</p>
            <div class="search-form">
                <input type="text" id="zipCode" class="form-input" placeholder="Enter your zip code" maxlength="5">
                <button onclick="searchLocalResources()" class="btn btn-primary">Search</button>
            </div>
            <div id="searchResults" class="search-results" style="display: none;">
                <h3>Local Resources</h3>
                <div class="results-list">
                    <!-- Results will be populated by JavaScript -->
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Wellness Module Styles */
.category-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: var(--spacing-xl);
    margin-bottom: var(--spacing-2xl);
}

.category-card {
    background: white;
    border-radius: var(--radius-xl);
    padding: var(--spacing-xl);
    box-shadow: var(--shadow-lg);
    border: 1px solid var(--gray-200);
    transition: all var(--transition-normal);
}

.category-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-xl);
}

.category-icon {
    font-size: 3rem;
    text-align: center;
    margin-bottom: var(--spacing-md);
}

.category-title {
    font-size: var(--font-size-xl);
    font-weight: 600;
    color: var(--gray-800);
    text-align: center;
    margin-bottom: var(--spacing-sm);
}

.category-description {
    text-align: center;
    color: var(--gray-600);
    margin-bottom: var(--spacing-lg);
}

.resource-list {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-md);
}

.resource-item {
    background: var(--gray-50);
    border-radius: var(--radius-lg);
    padding: var(--spacing-md);
}

.resource-title {
    font-size: var(--font-size-lg);
    font-weight: 600;
    color: var(--gray-800);
    margin-bottom: var(--spacing-xs);
}

.resource-description {
    color: var(--gray-600);
    margin-bottom: var(--spacing-sm);
    line-height: 1.5;
}

.resource-link {
    color: var(--primary-color);
    text-decoration: none;
    font-weight: 500;
    transition: color var(--transition-fast);
}

.resource-link:hover {
    color: var(--primary-dark);
    text-decoration: underline;
}

.resource-link.emergency {
    color: var(--danger-color);
    font-weight: 600;
}

.resource-link.emergency:hover {
    color: #dc2626;
}

/* Emergency Section */
.emergency-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: var(--spacing-lg);
    margin-bottom: var(--spacing-xl);
}

.emergency-card {
    background: var(--gradient-primary);
    color: white;
    border-radius: var(--radius-xl);
    padding: var(--spacing-xl);
    text-align: center;
    box-shadow: var(--shadow-lg);
    transition: all var(--transition-normal);
}

.emergency-card:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-xl);
}

.emergency-icon {
    font-size: 2.5rem;
    margin-bottom: var(--spacing-md);
}

.emergency-title {
    font-size: var(--font-size-lg);
    font-weight: 600;
    margin-bottom: var(--spacing-md);
}

.emergency-button {
    display: inline-block;
    background: white;
    color: var(--primary-color);
    padding: var(--spacing-md) var(--spacing-lg);
    border-radius: var(--radius-lg);
    text-decoration: none;
    font-weight: 600;
    font-size: var(--font-size-lg);
    transition: all var(--transition-fast);
}

.emergency-button:hover {
    background: var(--gray-100);
    transform: scale(1.05);
}

/* Wellness Tips */
.tips-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: var(--spacing-lg);
    margin-bottom: var(--spacing-xl);
}

.tip-card {
    background: white;
    border-radius: var(--radius-lg);
    padding: var(--spacing-lg);
    text-align: center;
    box-shadow: var(--shadow-md);
    border: 1px solid var(--gray-200);
}

.tip-icon {
    font-size: 2rem;
    margin-bottom: var(--spacing-sm);
}

.tip-title {
    font-size: var(--font-size-lg);
    font-weight: 600;
    color: var(--gray-800);
    margin-bottom: var(--spacing-sm);
}

.tip-content {
    color: var(--gray-600);
    line-height: 1.5;
}

/* Search Section */
.search-section {
    background: white;
    border-radius: var(--radius-xl);
    padding: var(--spacing-xl);
    box-shadow: var(--shadow-lg);
    border: 1px solid var(--gray-200);
    text-align: center;
}

.search-description {
    color: var(--gray-600);
    margin-bottom: var(--spacing-lg);
    font-size: var(--font-size-lg);
}

.search-form {
    display: flex;
    gap: var(--spacing-md);
    justify-content: center;
    align-items: center;
    margin-bottom: var(--spacing-lg);
}

.search-form .form-input {
    max-width: 200px;
}

.search-results {
    text-align: left;
    background: var(--gray-50);
    border-radius: var(--radius-lg);
    padding: var(--spacing-lg);
    margin-top: var(--spacing-lg);
}

/* Responsive Design */
@media (max-width: 768px) {
    .category-grid {
        grid-template-columns: 1fr;
    }
    
    .emergency-grid {
        grid-template-columns: 1fr;
    }
    
    .tips-grid {
        grid-template-columns: 1fr 1fr;
    }
    
    .search-form {
        flex-direction: column;
    }
    
    .search-form .form-input {
        max-width: 100%;
    }
}

@media (max-width: 480px) {
    .tips-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
function searchLocalResources() {
    const zipCode = document.getElementById('zipCode').value;
    const resultsDiv = document.getElementById('searchResults');
    
    if (!zipCode || zipCode.length !== 5) {
        alert('Please enter a valid 5-digit zip code.');
        return;
    }
    
    // Simulate search results (in real implementation, this would call an API)
    const mockResults = [
        {
            name: "Local Cancer Center",
            address: "123 Medical Way, " + getRandomCity(),
            phone: "555-0123",
            type: "Cancer Treatment Center"
        },
        {
            name: "Community Support Group",
            address: "456 Community St, " + getRandomCity(),
            phone: "555-0456",
            type: "Support Group"
        },
        {
            name: "Wellness Center",
            address: "789 Wellness Ave, " + getRandomCity(),
            phone: "555-0789",
            type: "Wellness Services"
        }
    ];
    
    const resultsList = resultsDiv.querySelector('.results-list');
    resultsList.innerHTML = '';
    
    mockResults.forEach(result => {
        const resultItem = document.createElement('div');
        resultItem.className = 'result-item';
        resultItem.innerHTML = `
            <div style="background: white; border-radius: var(--radius-lg); padding: var(--spacing-md); margin-bottom: var(--spacing-sm); border: 1px solid var(--gray-200);">
                <h4 style="font-weight: 600; color: var(--gray-800); margin-bottom: var(--spacing-xs);">${result.name}</h4>
                <p style="color: var(--gray-600); margin-bottom: var(--spacing-xs);">${result.type}</p>
                <p style="color: var(--gray-600); margin-bottom: var(--spacing-xs);">${result.address}</p>
                <a href="tel:${result.phone}" style="color: var(--primary-color); font-weight: 500;">📞 ${result.phone}</a>
            </div>
        `;
        resultsList.appendChild(resultItem);
    });
    
    resultsDiv.style.display = 'block';
}

function getRandomCity() {
    const cities = ["Anytown", "Springfield", "Riverside", "Franklin", "Georgetown"];
    return cities[Math.floor(Math.random() * cities.length)];
}

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    // Add enter key support for zip code search
    document.getElementById('zipCode').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            searchLocalResources();
        }
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>