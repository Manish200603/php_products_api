# PHP Products Inventory API

## Features
- Create Product API
- Get Product API
- Update Product API
- JSON File Storage
- Validation & Error Handling

## Run Project

```bash
php -S localhost:8000
```

## API Endpoints

### 1. Create Product
POST /products

Example JSON:
```json
{
  "name": "Laptop",
  "description": "Gaming Laptop",
  "price": 50000,
  "quantity": 5
}
```

### 2. Get Product
GET /products/1

### 3. Update Product
PUT /products/1

Example JSON:
```json
{
  "price": 45000
}
```
