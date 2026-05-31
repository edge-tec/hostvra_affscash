import os
import re

dir_path = "/Users/mizanurrahman/claude/affscash script/Affscash.net  latest script/project aapanel"

sections = [
    ("Introduction", "<p>AffsCash.net is committed to protecting the privacy and personal data of all users. This GDPR Compliance Policy explains how we collect, use, store, and protect personal data in accordance with the General Data Protection Regulation (EU) 2016/679 (GDPR) and applicable UK data protection laws.</p>"),
    
    ("Data Controller", "<p>For the purposes of GDPR, AffsCash.net acts as the Data Controller for personal data collected through our platform.</p>\n<p>We are responsible for determining the purposes and means of processing personal data.</p>"),
    
    ("Personal Data We Collect", "<p>We may collect and process the following categories of personal data:</p>\n\n<p><strong>3.1 Account Information</strong></p>\n<ul class=\"tos-list\">\n  <li>Full name</li>\n  <li>Email address</li>\n  <li>Username</li>\n  <li>Password (encrypted)</li>\n  <li>Payment details</li>\n</ul>\n\n<p><strong>3.2 Technical Data</strong></p>\n<ul class=\"tos-list\">\n  <li>IP address</li>\n  <li>Device type and browser</li>\n  <li>Operating system</li>\n  <li>Language settings</li>\n</ul>\n\n<p><strong>3.3 Usage & Tracking Data</strong></p>\n<ul class=\"tos-list\">\n  <li>Click activity</li>\n  <li>Conversion data</li>\n  <li>Traffic sources</li>\n  <li>Dashboard interactions</li>\n</ul>\n\n<p><strong>3.4 Communication Data</strong></p>\n<ul class=\"tos-list\">\n  <li>Support messages</li>\n  <li>Email correspondence</li>\n  <li>Live chat interactions</li>\n</ul>"),
    
    ("Purpose of Data Collection", "<p>We process personal data for the following purposes:</p>\n<ul class=\"tos-list\">\n  <li>Affiliate tracking and attribution</li>\n  <li>Fraud prevention and detection</li>\n  <li>Payment processing</li>\n  <li>Account management</li>\n  <li>Platform performance analysis</li>\n  <li>Legal and compliance obligations</li>\n  <li>Customer support</li>\n</ul>"),
    
    ("Legal Basis for Processing", "<p>We process personal data under the following lawful bases:</p>\n<ul class=\"tos-list\">\n  <li>Contractual necessity (affiliate program participation)</li>\n  <li>Legitimate interests (fraud prevention, analytics, platform security)</li>\n  <li>Legal obligations (tax, accounting, compliance)</li>\n  <li>Consent (where required for cookies or marketing communication)</li>\n</ul>"),
    
    ("Data Sharing", "<p>We may share personal data with:</p>\n<ul class=\"tos-list\">\n  <li>Advertisers (for conversion validation)</li>\n  <li>Payment processors (for affiliate payouts)</li>\n  <li>Fraud detection service providers</li>\n  <li>Hosting and cloud infrastructure providers</li>\n  <li>Legal or regulatory authorities (when required by law)</li>\n</ul>\n<p>We do not sell personal data to third parties.</p>"),
    
    ("Cookies & Tracking Technologies", "<p>AffsCash uses cookies, pixels, and tracking technologies to:</p>\n<ul class=\"tos-list\">\n  <li>Track affiliate referrals</li>\n  <li>Measure conversions</li>\n  <li>Prevent fraud</li>\n  <li>Improve platform performance</li>\n</ul>\n<p>Users may manage cookie preferences through their browser settings.</p>"),
    
    ("Data Retention", "<p>We retain personal data only as long as necessary for:</p>\n<ul class=\"tos-list\">\n  <li>Providing affiliate services</li>\n  <li>Legal compliance</li>\n  <li>Fraud prevention</li>\n  <li>Financial record-keeping</li>\n</ul>\n<p>After this period, data is securely deleted or anonymized.</p>"),
    
    ("Data Security", "<p>We implement strong technical and organizational measures, including:</p>\n<ul class=\"tos-list\">\n  <li>Encrypted data storage</li>\n  <li>Secure servers and firewalls</li>\n  <li>Access control restrictions</li>\n  <li>Regular security monitoring</li>\n</ul>\n<p>However, no system is 100% secure, and we cannot guarantee absolute protection.</p>"),
    
    ("International Data Transfers", "<p>As a global affiliate network, data may be transferred outside the EEA/UK.</p>\n<p>When this occurs, we ensure appropriate safeguards such as:</p>\n<ul class=\"tos-list\">\n  <li>Standard Contractual Clauses (SCCs)</li>\n  <li>Secure hosting providers</li>\n  <li>Compliance with applicable data protection laws</li>\n</ul>"),
    
    ("Data Subject Rights (GDPR Rights)", "<p>If you are located in the EEA or UK, you have the right to:</p>\n<ul class=\"tos-list\">\n  <li>Access your personal data</li>\n  <li>Request correction of inaccurate data</li>\n  <li>Request deletion (“right to be forgotten”)</li>\n  <li>Restrict or object to processing</li>\n  <li>Request data portability</li>\n  <li>Withdraw consent (where applicable)</li>\n</ul>\n<p>Requests can be submitted to our support team.</p>"),
    
    ("Automated Decision-Making", "<p>AffsCash may use automated systems for:</p>\n<ul class=\"tos-list\">\n  <li>Fraud detection</li>\n  <li>Traffic quality analysis</li>\n  <li>Risk scoring</li>\n</ul>\n<p>These systems may affect earnings or account status, but final decisions may involve human review where required.</p>"),
    
    ("Fraud Prevention & Monitoring", "<p>To protect the integrity of our platform, we may process data for:</p>\n<ul class=\"tos-list\">\n  <li>Detecting fraudulent traffic</li>\n  <li>Identifying suspicious activity</li>\n  <li>Preventing abuse of affiliate system</li>\n</ul>\n<p>This processing is necessary for legitimate business interests.</p>"),
    
    ("Data Requests & Contact", "<p>Users may exercise their GDPR rights by contacting us:</p>\n<ul class=\"tos-list\">\n  <li>Support Email: contact@affscash.net</li>\n  <li>Website: AffsCash.net</li>\n</ul>\n<p>We will respond to valid requests within the legally required timeframe (typically 30 days).</p>"),
    
    ("Policy Updates", "<p>We may update this GDPR Policy from time to time. Updates will be posted on this page with a revised effective date.</p>\n<p>Continued use of AffsCash.net indicates acceptance of the updated policy.</p>"),
    
    ("Acceptance", "<p>By using AffsCash.net, you acknowledge that:</p>\n<ul class=\"tos-list\">\n  <li>You have read this GDPR Compliance Policy</li>\n  <li>You understand how your data is processed</li>\n  <li>You agree to the described data practices</li>\n</ul>")
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

filepath = os.path.join(dir_path, "gdpr-compliance-policy.php")
if os.path.exists(filepath):
    with open(filepath, "r", encoding="utf-8") as f:
        file_content = f.read()
        
    # Replace Title
    file_content = re.sub(r'<title>.*?</title>', '<title>GDPR Compliance Policy — Affscash</title>', file_content)
    file_content = re.sub(r'<h1>.*?</h1>', '<h1>GDPR Compliance Policy</h1>', file_content)
    
    # Replace Notice Block
    notice_block = '<strong>Last Updated: June 1, 2026</strong><br>Applies To: AffsCash.net (“Company,” “we,” “us,” “our”) and all users, affiliates, and visitors within the European Economic Area (EEA), UK, and other applicable regions.'
    
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

