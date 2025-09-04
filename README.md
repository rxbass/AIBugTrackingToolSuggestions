# AIBugTrackingToolSuggestions
Usable for generating professional bug-tracking comments using open AI model.

Bug Tracking Comment Suggestions Backend
This project provides an API backend that generates professional, polite, and effective bug-tracking comments for tools like Jira, Azure DevOps, and Linear, using the OpenAI API.
It is designed to streamline communication between reporters and developers, producing short updates, detailed resolution notes, and lifecycle-based suggestions.
________________________________________
Features
•	Generates bug-tracking comments based on:
  o	Bug title, description, reporter
  o	Recent comments
  o	Screenshot availability
•	Lifecycle-aware suggestions:
  o	Initial Triage & Request for Details
  o	Confirmation of Issue
  o	Status Update / In Progress
  o	Resolution & Request for Verification
  o	Closing the Ticket
  o	Reopening the Ticket
•	Clear and polite communication with placeholders ([Reporter’s Name], [Browser], etc.)
•	REST API endpoint with CORS support
•	JSON input/output for easy integration
________________________________________
Requirements
  •	PHP 7.4+ or higher
  •	cURL enabled
  •	An OpenAI API Key

Setup
  1.	Clone or download this repository.
  2.	Open bug_comments.php (or your backend file).
  3.	Replace the API key placeholder with your OpenAI key

API Usage
  Endpoint
    POST /bug_comments.php

      Request Body (JSON)
       {
        "title": "[Ver1.0.1][contract] Contracts page got navigated when the contract created from the contact",
        "description":"Steps to Reproduce: \n 1. Login as an Admin\n2.Go to contact page\n3.View contact\n4.Create contract.\nactual_result:Contract page got navigated.\nexpected_result:Created contract should be listed in the contact.",
          "reporter": "Jo",
          "recent_comments": [],
          "has_screenshot": true
      }

      Response (JSON)

        {
          "success": true,
          "suggestions": "### Initial Triage & Request for Details\n- Hi Jo, thanks for the report! Could you please provide the [Browser] and [Environment] details where you encountered this issue? It will help us in our investigation.\n\n### Confirmation of Issue\n- Thanks for the detailed steps and the screenshot, Jo. I can confirm that the issue is reproducible as described.\n\n### Status Update / In Progress\n- Working on it, Jo. I’ll keep you updated on the progress.\n\n### Resolution & Request for Verification\n- Thanks for your patience, Jo. A fix has been deployed to staging — please verify if the created contract now lists correctly in the contact.\n\n### Closing the Ticket\n- Great news! The fix has been verified and deployed. Thank you for your assistance, [Developer's Name].\n\n### Reopening the Ticket\n- Hi [Developer's Name], it seems the issue persists for Jo. Please investigate further.",
          "timestamp": "2025-09-04 17:37:30"
      }

Check the html file for sample output.

Error Handling
•	400 – Validation failed (missing required fields like title/description)
•	405 – Method not allowed (only POST supported)
•	500 – Server error or OpenAI API issue

