# 🛒 Gestion des Commandes & Paiements

## 📦 6. Gestion des Commandes

### 📜 Lister les commandes
**GET** `/api/v1/admin/orders`

### 🔍 Voir une commande
**GET** `/api/v1/admin/orders/{id}`

### ✏️ Mettre à jour le statut
**PUT** `/api/v1/admin/orders/{id}/status`

### ❌ Annuler une commande
**DELETE** `/api/v1/admin/orders/{id}`

### 📦 Modèle de Données - Commandes (orders)

| Champ       | Type      | Description                            |
|------------|----------|----------------------------------------|
| id         | int      | Identifiant unique                    |
| user_id    | int      | Référence de l'utilisateur            |
| total_price| decimal  | Prix total de la commande             |
| status     | string   | Statut (en attente, en cours, expédiée, annulée) |
| created_at | timestamp| Date de création                      |
| updated_at | timestamp| Date de mise à jour                   |

## 🛍️ 7. Gestion des Paiements

### 💳 Traitement des paiements
**POST** `/api/v1/admin/payments`

### 📜 Lister les transactions
**GET** `/api/v1/admin/payments`

### 🔍 Détails d’un paiement
**GET** `/api/v1/admin/payments/{id}`

### 💰 Modèle de Données - Paiements (payments)

| Champ         | Type      | Description                          |
|--------------|----------|--------------------------------------|
| id           | int      | Identifiant unique                  |
| order_id     | int      | Référence de la commande            |
| payment_type | string   | Type de paiement (carte, PayPal...) |
| status       | string   | Statut (réussi, en attente, échoué) |
| transaction_id | string | ID transactionnel externe           |
| created_at   | timestamp| Date de création                    |

## 🚀 Workflow de Commande & Paiement

1️⃣ L'utilisateur passe une commande  
2️⃣ L'administrateur valide et change le statut  
3️⃣ Paiement associé (via une passerelle externe)  
4️⃣ Suivi et gestion des transactions  
