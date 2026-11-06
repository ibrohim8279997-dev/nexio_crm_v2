# Inventory flows and data model

## Purchase and receipt flow

```mermaid
flowchart LR
  A[Draft purchase] --> B[Manager approval]
  B --> C[Approved]
  C --> D[Partial receipt]
  D --> E{Fully received?}
  E -- No --> D
  E -- Yes --> F[Received]
  D --> G[Atomic stock increase]
  G --> H[Movement audit]
```

AI-created or AI-suggested purchases remain in `draft` and require a separate approval call. The agent cannot skip the approval operation by changing the client payload.

## Inventory count flow

```mermaid
flowchart LR
  A[Open count] --> B[Capture expected quantities]
  B --> C[Record counted quantities]
  C --> D[Human approval]
  D --> E[Atomic variance adjustment]
  E --> F[Movement audit]
```

## ER overview

```mermaid
erDiagram
  TENANT ||--o{ INVENTORY_PRODUCT : owns
  TENANT ||--o{ INVENTORY_WAREHOUSE : owns
  INVENTORY_PRODUCT ||--o{ INVENTORY_STOCK : balances
  INVENTORY_WAREHOUSE ||--o{ INVENTORY_STOCK : stores
  INVENTORY_SUPPLIER ||--o{ PURCHASE_ORDER : receives
  PURCHASE_ORDER ||--o{ PURCHASE_ITEM : contains
  PURCHASE_ORDER ||--o{ RECEIPT : has
  RECEIPT ||--o{ RECEIPT_ITEM : contains
  INVENTORY_COUNT ||--o{ COUNT_ITEM : contains
  INVENTORY_PRODUCT ||--o{ MOVEMENT : moves
```
