# AI Tool Kit - EduAI Platform

AI Tool Kit is a graduation project for an AI-powered education platform. The project combines a Laravel web platform, a NativePHP mobile application, and a set of AI services that help students and educators generate learning materials from prompts or WhatsApp messages.

## Demo Videos

Demo videos and project materials:

[Google Drive Demo](https://drive.google.com/drive/folders/1-JbfIvR9Nb1M_k3tjc9oUAyOHG2T3syi?usp=drive_link)

> Note: replace this link with the Google Drive demo videos link if the videos are stored separately.

## Repository Structure

```text
ai-tool-kit/
  Website/     Laravel web platform, backend API, admin dashboard, and WhatsApp webhook.
  MobileApp/   NativePHP mobile application for Android.
  AI/          AI service code and AI-related reference files.
```

## Project Overview

EduAI helps users create educational content quickly using AI. Users can generate presentations, quizzes, mind maps, audio narrations, video explainers, and simple educational animations. The platform supports both web and mobile experiences, with WhatsApp automation for fast access through chat.

## Team Members

| Name | Role |
| --- | --- |
| Abdulrahman Mohamed Eid Ali | Team Leader, Fullstack |
| Mayada Mohamed Abdel-Qader Mohamed | AI |
| Mohab Nasser Ahmed Ali | AI |
| Alaa Ahmed Ramadan Ahmed | AI |
| Asmaa Ayman Fathy Abdel-Moneim | Frontend |
| Abdulrahman Aboubakr Mahmoud Mohamed | Database |
| Omar Mohamed Hanafy Mohamed | UI & UX |
| Sara Mahmoud Ahmed Ramadan | Frontend |
| Rawda Gomaa El-Sayed Ahmed | Frontend |

## Main Features

- AI presentation generation
- AI quiz generation with public quiz sharing
- AI mind map generation
- Text-to-speech and audio narration generation
- Video explainer generation
- Educational animation generation
- User authentication and WhatsApp OTP verification
- Admin dashboard for users, plans, jobs, and platform activity
- Credit-based usage system for SaaS-style limits
- Mobile app interface for core tools
- WhatsApp agent for tool requests through chat messages

## Website

The `Website/` folder contains the main Laravel application. It includes the web dashboard, API endpoints, admin area, authentication, AI tool controllers, background jobs, and WhatsApp integration.

Important areas:

- `Website/resources/views/` - web and dashboard views.
- `Website/routes/api.php` - API routes.
- `Website/routes/web.php` - web routes.

## Mobile App

The `MobileApp/` folder contains the NativePHP mobile application. It provides a mobile-first UI for logging in, registering, verifying WhatsApp OTP, browsing tools, creating AI content, viewing jobs, and managing profile data.

Mobile authentication now supports:

- WhatsApp number login
- OTP delivery through WhatsApp
- OTP verification before token creation
- WhatsApp number verification during registration

Generated APK/AAB files are intentionally not included in the repository. Release builds should be uploaded separately through GitHub Releases or a deployment channel.

## AI Folder

The `AI/` folder contains AI-related service code copied from the backend for project review, documentation, and presentation. The runtime version of these services still exists inside `Website/app/Services`.

Included AI services cover:
- Quiz and question generation

## WhatsApp Agent

The WhatsApp agent allows users to interact with EduAI directly from WhatsApp. A user can send a message such as:

```text
Create a 5-slide presentation about renewable energy
```

or:

```text
Generate a quiz about photosynthesis
```

The WhatsApp flow works as follows:

1. A WhatsApp message reaches the webhook endpoint.
2. The platform detects the user intent.
3. The intent detector maps the message to a supported tool.
4. The platform extracts the topic, language, and generation parameters.
5. The proper AI generation job is created.
6. The user receives a WhatsApp response with the result or status.

Supported WhatsApp agent tools:

- Presentation
- Mind map
- Video explainer
- Audio narration
- Video animation
- Quiz

Key files:

- `Website/app/Http/Controllers/Api/V1/WhatsappWebhookController.php`
- `Website/app/Services/Whatsapp/WhatsappIntentDetector.php`
- `Website/app/Services/Whatsapp/MetaphiliaClient.php`
- `Website/app/Jobs/SendWhatsappToolResultJob.php`

## Technology Stack

- PHP
- Laravel
- Laravel Sanctum
- Livewire
- NativePHP Mobile
- MySQL
- SQLite
- Kotlin
- Alpine.js
- Tailwind CSS
- OpenAI-compatible AI API integration
- WhatsApp messaging API integration
- Android build tooling

## Security Notes

The repository excludes sensitive and generated files

## Project Status

This project is built as a graduation project and demonstrates a complete AI education platform across web, mobile, backend APIs, and WhatsApp automation.
