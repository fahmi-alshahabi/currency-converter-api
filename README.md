# Currency Converter API

A RESTful currency conversion web service written in PHP. It converts amounts between currencies using live exchange rates from the [Fixer.io](https://fixer.io) API, stores rates locally in XML, and returns responses in either **XML** or **JSON**. It also includes an update service and a browser-based interface for managing currencies.

## Features

- Currency conversion between 25 currencies, using GBP as the base currency
- Live exchange rates from Fixer.io, cached locally and refreshed every 8 hours
- Automatic fallback to built-in default rates if the external API is unavailable
- Responses in XML or JSON
- Update service supporting **PUT** (update a rate), **POST** (re-activate a currency) and **DELETE** (deactivate a currency)
- Input validation with consistent, numbered error codes
- Single-page HTML/JavaScript interface (Axios) for testing the update service

## Project structure

```
├── index.php            # Conversion service (GET)
├── update/
│   ├── index.php        # Update service (PUT / POST / DELETE)
│   └── interface.html   # Web interface for the update service
├── config.example.php   # Template for the API key configuration
└── currencies.xml       # Generated automatically on first run
```

## Getting started

### Prerequisites

- PHP 7.4+ with the SimpleXML and DOM extensions
- A free Fixer.io API key (optional – the service falls back to default rates without one)

### Configuration

Copy the example configuration file and add your API key:

```bash
cp config.example.php config.php
```

Alternatively, set the `FIXER_API_KEY` environment variable.

### Run locally

```bash
php -S localhost:8000
```

## API usage

### Convert currency

```
GET /index.php?from=GBP&to=USD&amnt=100&format=json
```

| Parameter | Description |
|-----------|-------------|
| `from` | Three-letter code of the currency to convert from |
| `to` | Three-letter code of the currency to convert to |
| `amnt` | Amount to convert (decimal number) |
| `format` | `xml` or `json` (default: `xml`) |

**Example response (JSON)**

```json
{
  "at": "2025-01-10 12:00:00",
  "rate": 1.27,
  "from": { "code": "GBP", "curr": "Pound Sterling", "loc": "United Kingdom", "amnt": 100 },
  "to":   { "code": "USD", "curr": "US Dollar", "loc": "United States", "amnt": 127 }
}
```

### Update service

```
GET /update/index.php?cur=USD&action=put&format=xml
```

| Action | Description |
|--------|-------------|
| `put`  | Updates the rate for an active currency |
| `post` | Re-activates a previously deactivated currency |
| `del`  | Deactivates a currency so it is no longer available for conversion |

The base currency (GBP) cannot be updated or deleted.

Open `update/interface.html` in the browser for a form-based interface to these actions.

### Error codes

| Code | Meaning |
|------|---------|
| 1000 | Required parameter is missing |
| 1100 | Parameter not recognised |
| 1200 | Currency type not recognised |
| 1300 | Currency amount must be a decimal number |
| 1400 | Format must be xml or json |
| 1500 | Error in service |
| 2000 | Action not recognised or is missing |
| 2100 | Currency code in wrong format or is missing |
| 2200 | Currency code not found for update |
| 2300 | No rate listed for this currency |
| 2400 | Cannot update base currency |
| 2500 | Error in service |
