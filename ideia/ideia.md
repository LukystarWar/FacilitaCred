📘 Project Requirements — Facilita Cred

Loan Management System • VSA Architecture • Pure PHP + MySQL

1. Project Overview

Facilita Cred is a lightweight and efficient loan management system designed primarily for tablet usage.
The system will follow a mobile-first design, with minimal JS, clean interfaces, and a modular code organization using VSA (View–Service–Action) architecture.

Tech Stack:

Backend: Pure PHP

Database: MySQL

Frontend: HTML + CSS + minimal JavaScript

Development workflow: Structured and executed with Claude CLI, completing one feature at a time before moving to the next.

The goal is to maintain simplicity, speed, and low deployment costs.

2. Business Rules
2.1. Wallets (Carteiras)

The system will support multiple wallets. Each wallet has:

Name

Balance

Individual transaction history

Entries, withdrawals, transfers

Profit tracking

Available actions per wallet:

Transfer funds between wallets

Withdraw

Deposit

Edit wallet information

Deactivate wallet

Every operation must generate a corresponding history record.

2.2. Loans
2.2.1. Interest Rules

One-time payment (up-front): 20% interest

Installments: 15% per month

Example: 3 months → 3 × 15% = 45% total interest

2.2.2. Creating a Loan

When creating a new loan, the system must allow:

Select an existing client

Enter loan amount

Define payment date

Choose number of installments

Automatically calculate installment values

Allow manual editing of each installment value

Display:

New total amount

Applied interest

Parcel distribution

Select which wallet will provide the loan funds

After confirmation:

The loan amount is deducted from the selected wallet

A transaction record is created in the wallet history

2.2.3. Payment Processing

When an installment is paid:

The paid amount is automatically credited back to the originating wallet

A transaction record must be added containing:

Date

Amount

Type: "installment payment"

2.2.4. Loan Details

Each loan must have a dedicated detail view (or modal) containing:

Client information

Wallet used for the loan

Loan amount + interest

Installments (paid, pending, overdue)

Timeline

Total profit

Full history of transactions

Administrative actions

3. Clients

The client listing will evolve from a simple table into a richer module including:

Search functionality

Sorting

Quick financial summary

Indicator of active loans

Detailed client info

Add/Edit forms via modals, avoiding unnecessary page navigation

4. UI/UX Guidelines
4.1. Structure

Preferred approach:

Minimal screens

Central modals for forms and actions

Lightweight JS

Smooth tablet experience

(Performance will be monitored during deployment to ensure feasibility.)

4.2. Sidebar and Feature Flow

Each sidebar item represents a system module.
Development workflow rule:

Complete one sidebar feature 100% before starting the next one.

5. Project Goal

Build a clean and efficient loan management system with full control over:

Wallets

Loans

Payments

History

Profitability

Focusing on:

Speed

Clarity

Performance

Maintainability

Low infrastructure cost

Excellent usability on tablets