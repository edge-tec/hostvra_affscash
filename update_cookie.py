import os
import re

dir_path = "/Users/mizanurrahman/claude/affscash script/Affscash.net  latest script/project aapanel"

sections = [
    ("Introduction", "<p>This Cookie Policy explains how AffsCash.net uses cookies and similar tracking technologies when you visit our website, use our affiliate dashboard, or interact with our services.</p>\n<p>By using our platform, you agree to the use of cookies as described in this policy.</p>"),
    
    ("What Are Cookies?", "<p>Cookies are small text files stored on your device (computer, mobile, or tablet) when you visit a website. They help websites function properly and improve user experience.</p>\n<p>Cookies may store:</p>\n<ul class=\"tos-list\">\n  <li>Login sessions</li>\n  <li>User preferences</li>\n  <li>Tracking identifiers</li>\n  <li>Analytics data</li>\n</ul>"),
    
    ("Why We Use Cookies", "<p>AffsCash uses cookies for the following purposes:</p>\n\n<p><strong>3.1 Essential Cookies</strong></p>\n<p>These are required for platform functionality:</p>\n<ul class=\"tos-list\">\n  <li>User authentication</li>\n  <li>Dashboard access</li>\n  <li>Security and session management</li>\n</ul>\n<p>Without these cookies, the platform cannot function properly.</p>\n\n<p><strong>3.2 Affiliate Tracking Cookies</strong></p>\n<p>These cookies are used to:</p>\n<ul class=\"tos-list\">\n  <li>Track affiliate referrals</li>\n  <li>Attribute conversions correctly</li>\n  <li>Ensure accurate commission calculation</li>\n  <li>Prevent duplicate tracking</li>\n</ul>\n\n<p><strong>3.3 Performance & Analytics Cookies</strong></p>\n<p>We use analytics cookies to:</p>\n<ul class=\"tos-list\">\n  <li>Understand user behavior</li>\n  <li>Improve dashboard performance</li>\n  <li>Monitor system usage</li>\n  <li>Detect technical issues</li>\n</ul>\n\n<p><strong>3.4 Fraud Prevention Cookies</strong></p>\n<p>Cookies also help us:</p>\n<ul class=\"tos-list\">\n  <li>Detect suspicious activity</li>\n  <li>Prevent bot traffic</li>\n  <li>Identify invalid clicks or conversions</li>\n  <li>Protect advertisers and affiliates</li>\n</ul>\n\n<p><strong>3.5 Functionality Cookies</strong></p>\n<p>These cookies allow:</p>\n<ul class=\"tos-list\">\n  <li>Language preferences</li>\n  <li>Dashboard customization</li>\n  <li>Improved user experience</li>\n</ul>"),
    
    ("Third-Party Cookies", "<p>Some cookies may be placed by third parties such as:</p>\n<ul class=\"tos-list\">\n  <li>Advertisers (for conversion tracking)</li>\n  <li>Payment processors</li>\n  <li>Analytics providers</li>\n  <li>Fraud detection services</li>\n</ul>\n<p>These third parties may collect data according to their own privacy policies.</p>"),
    
    ("Cookie Retention Period", "<p>Cookies may be stored for different durations:</p>\n<ul class=\"tos-list\">\n  <li>Session cookies: Deleted when you close your browser</li>\n  <li>Persistent cookies: Stored until they expire or are manually deleted</li>\n</ul>\n<p>Tracking cookies may last from a few days to several months depending on purpose.</p>"),
    
    ("How We Use Tracking Technologies", "<p>In addition to cookies, we may use:</p>\n<ul class=\"tos-list\">\n  <li>Pixel tags</li>\n  <li>Web beacons</li>\n  <li>Device identifiers</li>\n  <li>Server-side tracking</li>\n</ul>\n<p>These technologies help ensure accurate affiliate attribution and fraud prevention.</p>"),
    
    ("Managing Cookies", "<p>You can control or disable cookies through your browser settings.</p>\n<p>However, please note:</p>\n<ul class=\"tos-list\">\n  <li>Disabling cookies may affect dashboard functionality</li>\n  <li>Tracking accuracy may be reduced</li>\n  <li>Some features may not work properly</li>\n</ul>"),
    
    ("Cookie Consent", "<p>Where required by law (EU/UK users), we obtain consent before placing non-essential cookies.</p>\n<p>By continuing to use AffsCash.net, you consent to:</p>\n<ul class=\"tos-list\">\n  <li>Essential cookies</li>\n  <li>Tracking cookies required for affiliate attribution</li>\n  <li>Analytics and fraud prevention cookies</li>\n</ul>"),
    
    ("Data Protection", "<p>All cookie-related data is processed in accordance with our:</p>\n<ul class=\"tos-list\">\n  <li>GDPR Compliance Policy</li>\n  <li>Privacy Policy</li>\n  <li>Security standards</li>\n</ul>\n<p>We do not sell cookie data to third parties.</p>"),
    
    ("International Use", "<p>AffsCash operates globally. Cookie usage may vary depending on your location and applicable laws.</p>\n<p>Users in the EEA/UK are provided with additional rights under GDPR.</p>"),
    
    ("Updates to This Policy", "<p>We may update this Cookie Policy at any time to reflect:</p>\n<ul class=\"tos-list\">\n  <li>Legal requirements</li>\n  <li>Technical changes</li>\n  <li>Platform improvements</li>\n</ul>\n<p>Updates will be posted on this page with a revised date.</p>"),
    
    ("Acceptance", "<p>By using AffsCash.net, you acknowledge and agree that:</p>\n<ul class=\"tos-list\">\n  <li>Cookies are used as described in this policy</li>\n  <li>You understand how tracking technologies work</li>\n  <li>You accept responsibility for managing cookie settings in your browser</li>\n</ul>")
]

# Create TOC and Accordions
toc_html = ""
accordions_html = ""

for i, (title, content) in enumerate(sections, 1):
    num_str = f"{i:02d}"
    
    # TOC
    toc_html += f'          <li><a href="#section-{i}"><span class="toc-num">{num_str}</span> <span class="toc-text">{title}</span></a></li>\n'
    
    # Accordion
    is_open = ' is-open' if i == 1 else ''
    accordions_html += f"""    <!-- Section {i} -->
    <section class="tos-section{is_open}" id="section-{i}">
      <div class="accordion-header">
        <span class="section-number">{num_str}</span>
        <h2>{title}</h2>
        <svg class="accordion-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
      </div>
      <div class="accordion-content">
        {content}
      </div>
    </section>

"""

filepath = os.path.join(dir_path, "cookie-policy.php")
if os.path.exists(filepath):
    with open(filepath, "r", encoding="utf-8") as f:
        file_content = f.read()
        
    # Replace Title
    file_content = re.sub(r'<title>.*?</title>', '<title>Cookie Policy — Affscash</title>', file_content)
    file_content = re.sub(r'<h1>.*?</h1>', '<h1>Cookie Policy</h1>', file_content)
    
    # Replace Notice Block
    notice_block = '<strong>Last Updated: June 1, 2026</strong><br>Applies To: AffsCash.net (“Company,” “we,” “us,” “our”)'
    
    file_content = re.sub(r'<div class="notice-block">.*?</div>', f'<div class="notice-block">\n      {notice_block}\n    </div>', file_content, flags=re.DOTALL)
    
    # Replace TOC
    file_content = re.sub(r'<ul class="toc-list">.*?</ul>', f'<ul class="toc-list">\n{toc_html}        </ul>', file_content, flags=re.DOTALL)
    
    # Replace Main Content (Accordions)
    match = re.search(r'(<div class="notice-block">.*?</div>)(.*?)(</main>)', file_content, re.DOTALL)
    if match:
        new_file_content = file_content[:match.end(1)] + "\n\n" + accordions_html + match.group(3) + file_content[match.end(3):]
        with open(filepath, "w", encoding="utf-8") as f:
            f.write(new_file_content)
        print(f"Updated {filepath}")
    else:
        print(f"Failed to match structure in {filepath}")

