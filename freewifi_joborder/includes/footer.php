<style>
    /* Footer Styling */
    :root {
        --primary-color: #1e40af;
        --primary-dark: #1e3a8a;
        --accent-color: #0891b2;
        --background-dark: #1f2937;
        --background-footer: #111827;
        --text-light: rgba(255, 255, 255, 0.9);
        --text-muted: rgba(255, 255, 255, 0.7);
        --border-color: rgba(255, 255, 255, 0.1);
    }

    footer {
        background: linear-gradient(135deg, var(--background-dark) 0%, var(--background-footer) 100%);
        color: var(--text-light);
        margin-top: auto;
    }

    .footer-top {
        background-color: var(--background-dark);
        padding: 50px 20px 40px;
        border-top: 3px solid var(--accent-color);
    }

    .footer-container {
        max-width: 1400px;
        margin: 0 auto;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: 40px;
    }

    .footer-section {
        display: flex;
        flex-direction: column;
        gap: 18px;
    }

    .footer-section h3 {
        font-size: 16px;
        font-weight: 800;
        color: white;
        letter-spacing: 1px;
        border-bottom: 3px solid var(--accent-color);
        padding-bottom: 12px;
        text-transform: uppercase;
    }

    .footer-section p {
        font-size: 13px;
        line-height: 1.8;
        color: var(--text-muted);
    }

    .contact-info {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .contact-item {
        display: flex;
        gap: 12px;
        align-items: flex-start;
        font-size: 13px;
        color: var(--text-muted);
        line-height: 1.6;
    }

    .contact-icon {
        font-size: 18px;
        color: var(--accent-color);
        min-width: 24px;
        margin-top: 2px;
        flex-shrink: 0;
    }

    .mission-vision-container {
        display: flex;
        flex-direction: column;
        gap: 25px;
    }

    .mission-vision-item {
        display: flex;
        flex-direction: column;
        gap: 10px;
        padding: 15px;
        background-color: rgba(8, 145, 178, 0.08);
        border-left: 4px solid var(--accent-color);
        border-radius: 4px;
    }

    .mission-vision-item h4 {
        font-size: 13px;
        font-weight: 700;
        color: var(--accent-color);
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .mission-vision-item p {
        font-size: 12px;
        line-height: 1.7;
        color: var(--text-muted);
    }

    .mission-vision-item p:first-of-type {
        font-style: italic;
        color: #e0f2fe;
        font-weight: 500;
    }

    .mission-vision-item ul {
        list-style: none;
        display: flex;
        flex-direction: column;
        gap: 7px;
        margin-top: 8px;
    }

    .mission-vision-item li {
        font-size: 12px;
        color: var(--text-muted);
        padding-left: 18px;
        position: relative;
        line-height: 1.5;
    }

    .mission-vision-item li::before {
        content: '●';
        position: absolute;
        left: 0;
        color: var(--accent-color);
        font-size: 12px;
    }

    .mandate-vision-container {
        display: flex;
        flex-direction: column;
        gap: 25px;
    }

    .mandate-item {
        display: flex;
        flex-direction: column;
        gap: 10px;
        padding: 15px;
        background-color: rgba(8, 145, 178, 0.08);
        border-left: 4px solid var(--accent-color);
        border-radius: 4px;
    }

    .mandate-item h4 {
        font-size: 13px;
        font-weight: 700;
        color: var(--accent-color);
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .mandate-reference {
        font-size: 11px;
        color: var(--accent-color);
        font-weight: 600;
        margin-top: 8px;
        padding-top: 10px;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }
    .core-values-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
        margin-top: 10px;
    }

    .core-value-item {
        background-color: rgba(8, 145, 178, 0.08);
        border-left: 3px solid var(--accent-color);
        padding: 10px 12px;
        border-radius: 3px;
    }

    .core-value-item h5 {
        font-size: 12px;
        font-weight: 700;
        color: var(--accent-color);
        margin-bottom: 4px;
    }

    .core-value-item p {
        font-size: 11px;
        color: var(--text-muted);
        line-height: 1.5;
    }

    /* Footer Bottom */
    .footer-bottom {
        background-color: var(--background-footer);
        padding: 30px 20px;
        border-top: 1px solid var(--border-color);
    }

    .footer-bottom-content {
        max-width: 1400px;
        margin: 0 auto;
        padding: 0 20px;
        text-align: center;
    }

    .footer-bottom-left {
        font-size: 12px;
        color: var(--text-muted);
        letter-spacing: 0.3px;
    }

    /* Responsive Design */
    @media (max-width: 1024px) {
        .footer-container {
            grid-template-columns: 1fr;
            gap: 40px;
        }
    }

    @media (max-width: 768px) {
        .footer-top {
            padding: 40px 15px 30px;
        }

        .footer-container {
            gap: 35px;
        }

        .footer-section h3 {
            font-size: 15px;
        }

        .mission-vision-item {
            padding: 12px;
        }

        .core-values-grid {
            grid-template-columns: 1fr;
        }

        .core-value-item {
            grid-column: auto !important;
        }

        .footer-bottom {
            padding: 25px 15px;
        }
    }

    @media (max-width: 480px) {
        .footer-top {
            padding: 30px 12px 20px;
        }

        .footer-container {
            gap: 25px;
        }

        .footer-section h3 {
            font-size: 14px;
            padding-bottom: 10px;
        }

        .footer-section p,
        .footer-section li {
            font-size: 12px;
        }

        .contact-info {
            gap: 12px;
        }

        .contact-item {
            font-size: 12px;
        }

        .mission-vision-item {
            padding: 10px;
        }

        .mission-vision-item h4 {
            font-size: 12px;
        }

        .mission-vision-item p {
            font-size: 11px;
        }

        .footer-bottom {
            padding: 20px 12px;
        }

        .footer-bottom-left {
            font-size: 11px;
        }
    }
</style>

<footer>
    <!-- Footer Top Section -->
    <div class="footer-top">
        <div class="footer-container">
            <!-- Mission & Vision Section -->
            <div class="footer-section">
                <h3>DICT Mission & Vision</h3>
                <div class="mission-vision-container">
                    <div class="mission-vision-item">
                        <h4>Mission</h4>
                        <p>"DICT of the people and for the people."</p>
                        <p>The Department of Information and Communications Technology commits to:</p>
                        <ul>
                            <li>Provide every Filipino access to vital ICT infrastructure and services</li>
                            <li>Ensure sustainable growth of Philippine ICT-enabled industries</li>
                            <li>Establish a One Digitized Government, One Nation</li>
                            <li>Support the administration in fully achieving its goals</li>
                            <li>Be the enabler, innovator, achiever, and leader in pushing the country's development</li>
                        </ul>
                    </div>
                    <div class="mission-vision-item">
                        <h4>Vision</h4>
                        <p>"An innovative, safe and happy nation that thrives through and is enabled by Information and Communications Technology."</p>
                        <p>DICT aspires for the Philippines to develop and flourish through innovation and constant development of ICT in the pursuit of a progressive, safe, secured, contented and happy Filipino nation.</p>
                    </div>
                </div>
            </div>

            <!-- Mandate Section -->
            <div class="footer-section">
                <h3>Mandate & Core Values</h3>
                <div class="mandate-vision-container">
                    <div class="mandate-item">
                        <h4>Mandate</h4>
                        <p>
                            The Department of Information and Communications Technology (DICT) shall be the primary policy, planning, coordinating, implementing, and administrative entity of the Executive Branch of the government that will plan, develop and promote the national ICT development agenda.
                        </p>
                        <div class="mandate-reference">RA 10844</div>
                    </div>
                    <div class="mandate-item">
                        <h4>Core Values</h4>
                        <div class="core-values-grid">
                            <div class="core-value-item">
                                <h5>Dignity</h5>
                                <p>Respect and honor in all our dealings</p>
                            </div>
                            <div class="core-value-item">
                                <h5>Integrity</h5>
                                <p>Honesty and strong moral principles</p>
                            </div>
                            <div class="core-value-item">
                                <h5>Competency</h5>
                                <p>Excellence in skills and knowledge</p>
                            </div>
                            <div class="core-value-item">
                                <h5>Compassion</h5>
                                <p>Empathy and concern for others</p>
                            </div>
                            <div class="core-value-item" style="grid-column: 1 / -1;">
                                <h5>Transparency</h5>
                                <p>Openness and clarity in actions and decisions</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact Section -->
            <div class="footer-section">
                <h3>Contact Information</h3>
                <div class="contact-info">
                    <div class="contact-item">
                        <span class="contact-icon">📍</span>
                        <span>
                            Department of Information and Communications Technology<br>
                            Region II Office, Tuguegarao City, Cagayan Valley, Philippines
                        </span>
                    </div>
                    <div class="contact-item">
                        <span class="contact-icon">📞</span>
                        <span>(078) 825 1654</span>
                    </div>
                    <div class="contact-item">
                        <span class="contact-icon">✉️</span>
                        <span>support@freewifi.gov.ph</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer Bottom Section -->
    <div class="footer-bottom">
        <div class="footer-bottom-content">
            <div class="footer-bottom-left">
                <span>&copy; <?php echo date('Y'); ?> Department of Information and Communications Technology (DICT). All rights reserved.</span>
            </div>
        </div>
    </div>
</footer>
