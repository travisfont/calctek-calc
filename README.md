# Calctek Calculator

## Table of Contents

- [Installation](#installation)
- [CalculationService](#calculationservice)
  - [Elements](#elements)
  - [Mathematical Precedence (Operator Priority)](#mathematical-precedence-operator-priority)
  - [Shunting-Yard Parser Integration](#shunting-yard-parser-integration)
- [Middleware](#middleware)
  - [AutoGuestAuthentication](#autoguestauthentication)
- [Migrations](#migrations)
- [JavaScript](#javascript)
  - [math.js](#mathjs)
- [Rules / CS Fixer](#rules--cs-fixer)

## Installation

Follow these steps to set up the project locally:

1. **Clone the repository:**
   ```bash
   git clone <your-repository-url>
   cd calctek-calc
   ```

2. **Install PHP Dependencies:**
   ```bash
   composer install
   ```

3. **Install JavaScript Dependencies:**
   ```bash
   npm install
   ```

4. **Environment Configuration:**
   Copy the example environment file and generate a new application encryption key.
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   *Make sure to update the `.env` file with your local database credentials (DB_CONNECTION, DB_DATABASE, DB_USERNAME, DB_PASSWORD).*

5. **Run Database Migrations:**
   Set up the necessary database tables.
   ```bash
   php artisan migrate
   ```

6. **Compile Frontend Assets:**
   Build the Vue.js components and Tailwind/CSS assets.
   ```bash
   npm run build
   # or run 'npm run dev' to keep the Vite dev server running
   ```

7. **Start the Development Server:**
   ```bash
   php artisan serve
   ```
   The application will be accessible at `http://localhost`.

8. **Run Tests:**
   Execute the test suite to ensure everything is working correctly.
   ```bash
   php artisan test
   ```

## CalculationService

### Elements

In the `CalculationService` class, the terminology **element** is used to refer to an individual piece or part of a mathematical expression. It's the building block of the calculation.

When someone types in an expression like `12.5 + -3 * 4`, the service breaks that single string down into a list of its individual components.

An "element" in our code is either:

1. **A number**: This can be a whole integer (`12`), a decimal (`12.5`), or a negative number (`-3`).
2. **An operator**: The mathematical symbols `+`, `-`, `*`, or `/`.

#### Example

For the expression `12.5 + -3 * 4`, the resulting "elements" would be an array that looks exactly like this:
```php
[
    "12.5",
    "+",
    "-3",
    "*",
    "4"
]
```

The `CalculationService` takes the long text string from the user, separates it into these intuitive **elements**, and then feeds them through the algorithm to get the final mathematical result.

### Mathematical Precedence (Operator Priority)

In the standard mathematical order of operations (often remembered as PEMDAS/BODMAS), multiplication and division must be calculated **before** addition and subtraction.

In the `CalculationService` class, **Priority** is defined exactly to enforce this rule using a simple associative array mapping:

#### Example

```php
$operatorPriorities = [
    '+' => 1,
    '-' => 1,
    '*' => 2,
    '/' => 2,
];
```

The values (`1` or `2`) dictate the mathematical precedence:
- **`*` (Multiplication)** and **`/` (Division)** hold a higher priority of **`2`**.
- **`+` (Addition)** and **`-` (Subtraction)** hold a lower priority of **`1`**.

When the `reorderElementsByPriority()` method organizes the parsed expression (using a stack-based approach called the Shunting-yard algorithm), it looks at this `$operatorPriorities` array to guarantee that operators with a `2` are always positioned to be evaluated before the operators with a `1`.

### Shunting-Yard Parser Integration

The [**Shunting-Yard algorithm**](https://en.wikipedia.org/wiki/Shunting_yard_algorithm) is a method for parsing mathematical expressions specified in infix notation (e.g., `3 + 4 * 2`) and converting them into postfix notation (also known as Reverse Polish Notation or RPN, e.g., `3 4 2 * +`).

#### How it is Integrated

The integration in `CalculationService` is broken down into a clean, three-step pipeline within the main `calculate()` method:

1. **Tokenization (`extractNumbersAndOperators`)**
   Before the Shunting-Yard algorithm can do its job, the raw string expression (like `"3+4*2"`) is parsed into a flat array of "tokens" (numbers and operators). For example, it becomes `['3', '+', '4', '*', '2']`.

2. **The Shunting-Yard Conversion (`reorderElementsByPriority`)**
   This is where the actual Shunting-Yard algorithm lives. It takes the array of tokens (Infix) and reorders them into Postfix notation.
   - It maintains an `$output` array (for the final RPN expression) and an `$operators` stack.
   - It defines priorities: `*` and `/` have a weight of `2`, while `+` and `-` have a weight of `1`.
   - As it loops through the tokens:
     - **Numbers** go straight into the `$output` array.
     - **Operators** are compared against what is currently on top of the `$operators` stack. If the operator on the stack has greater or equal precedence (checked via `shouldPopOperator()`), it gets popped off the stack and added to the `$output` array before the new operator is pushed onto the stack.
   - Finally, any remaining operators on the stack are popped into the `$output` array.
   - *Result:* `['3', '+', '4', '*', '2']` becomes `['3', '4', '2', '*', '+']`.

3. **Evaluation (`evaluateOrderedElements`)**
   Once the expression is in Postfix/RPN format, evaluating it becomes incredibly simple. The script iterates through the new array using a single `$stack`:
   - If it sees a number, it pushes it to the stack.
   - If it sees an operator (like `*`), it pops the top two numbers off the stack (`2` and `4`), applies the operator (`4 * 2 = 8`), and pushes the result (`8`) back onto the stack.
   - When it reaches the end, the final remaining number on the stack is the answer.

#### Why is it Being Used Here?

1. **Correct Order of Operations (PEMDAS/BODMAS)**
   The primary reason for using this algorithm is to respect standard mathematical precedence. Without it, a simple left-to-right parser would calculate `3 + 4 * 2` as `14` `((3 + 4) * 2)` instead of the mathematically correct `11` `(3 + (4 * 2))`. The Shunting-Yard stack logic naturally handles this prioritization.

2. **Security (Avoiding `eval()`)**
   In PHP, the easiest (and most dangerous) way to evaluate a string expression is to use the built-in `eval()` function or to pass it directly to a database query. However, parsing arbitrary user strings through `eval()` is a massive security vulnerability (Remote Code Execution). By building a Shunting-Yard parser, we ensure that the application processes only strict numbers and the allowed operators (`+`, `-`, `*`, `/`), making it completely secure against injection attacks.

3. **Separation of Concerns & Maintainability**
   By breaking the process into tokenization -> conversion (Shunting-Yard) -> evaluation, the code is highly modular. If we ever wanted to add support for parentheses `()` or exponents `^`, we only have to update the tokenization and the priority weights. The evaluation logic would largely remain untouched.

## Middleware

### AutoGuestAuthentication

The `AutoGuestAuthentication` middleware is designed to seamlessly handle public visitors to the API while maintaining strict isolation of calculation history. Instead of forcing users to explicitly register or log in to use the calculator, the API handles registration and authentication behind the scenes.

#### How It Works

`AutoGuestAuthentication` acts as a standalone authentication middleware for the API (replacing the standard `auth:sanctum` middleware, which would otherwise prematurely throw a `401 Unauthenticated` exception due to its higher middleware priority before a guest could be created).

When an incoming request hits the API:
1. **If a valid Sanctum token is provided:** The middleware detects it (`Auth::guard('sanctum')->check()`), sets the default guard to Sanctum (`Auth::shouldUse('sanctum')`), and allows the request to proceed normally.
2. **If a valid token is NOT provided:** The middleware will:
   - Dynamically create a new `User` record marked as a guest (`is_guest = true`).
   - Generate a new Laravel Sanctum personal access token for this temporary user.
   - Authenticate the request on the fly and set the default guard to Sanctum.
   - Append the newly generated token to the outgoing HTTP response in a custom header called `X-Guest-Token`.

#### How to Test on the Frontend

For the client application to correctly utilize this guest system, it must save the token provided in the first response and send it in subsequent requests.

**1. The Initial Request (No Token)**
When the user visits the app for the first time and performs an action (like submitting a calculation), make an unauthenticated request. The server will respond with the calculated result AND the `X-Guest-Token`.

```javascript
fetch('http://127.0.0.1/api/v1/calculations', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ expression: "10 + 20" })
}).then(response => {
    // Check if the server gave us a guest token
    const newGuestToken = response.headers.get('X-Guest-Token');
    if (newGuestToken) {
        // Save it to localStorage for future requests
        localStorage.setItem('calculator_token', newGuestToken);
    }
    return response.json();
});
```

**2. Subsequent Requests (Using Token)**
Once the token is saved, all future requests (like fetching their history) must include it in the `Authorization` header.

```javascript
// Retrieve the token from storage
const token = localStorage.getItem('calculator_token');

fetch('http://127.0.0.1/api/v1/calculations', {
    method: 'GET',
    headers: { 
        'Accept': 'application/json',
        'Authorization': `Bearer ${token}` 
    }
})
.then(response => response.json())
.then(data => console.log('Calculation History:', data));
```

## Migrations

Here is an explanation of all the migration files in this project. They can be broken down into two main categories: Laravel's standard boilerplate migrations and the custom migrations specific to your Calculator application.

### 1. Standard Laravel Boilerplate Migrations
These migrations are included by default in a new Laravel installation to handle authentication, caching, queues, and API tokens.

*   `0001_01_01_000000_create_users_table.php`
    *   **Description**: Creates the core authentication tables.
    *   **Tables Created**:
        *   `users`: Stores user accounts (id, name, email, password, etc.).
        *   `password_reset_tokens`: Stores tokens used when a user forgets their password and requests a reset link.
        *   `sessions`: Stores user session data when the application is configured to use the database session driver instead of files or cookies.
*   `0001_01_01_000001_create_cache_table.php`
    *   **Description**: Sets up the infrastructure for database-driven caching.
    *   **Tables Created**: `cache` and `cache_locks`. These allow Laravel to store cached data and manage resource locks directly in your database.
*   `0001_01_01_000002_create_jobs_table.php`
    *   **Description**: Prepares the database for background job processing (queues).
    *   **Tables Created**: `jobs`, `job_batches`, and `failed_jobs`. If you dispatch tasks to run in the background (like sending emails), these tables hold the job data until a queue worker processes them.
*   `2026_02_25_224219_create_personal_access_tokens_table.php`
    *   **Description**: Creates the `personal_access_tokens` table, which is used by **Laravel Sanctum**. This allows your API to issue and verify authentication tokens so users (or guests) can securely make requests to the calculator endpoints.

---

### 2. Custom Application Migrations

These migrations were specifically created for the project's business logic (managing calculation history and tying it to users/guests).

*   `2026_02_25_224257_create_calculations_table.php`
    *   **Description**: Creates the main table for storing calculation history.
    *   **Fields**:
        *   `id`: The primary key.
        *   `expression`: A string column that stores the math problem (ex: `"5 + 10"` or `"sqrt(16)"`).
        *   `result`: A decimal column (16 digits total, 8 decimal places) to store the exact mathematical answer.
        *   `timestamps`: Adds `created_at` and `updated_at`.
*   `2026_02_26_200645_add_is_guest_to_users_table.php`
    *   **Description**: Alters the existing `users` table.
    *   **Change**: Adds a boolean column called `is_guest` that defaults to `false`. This supports your application's guest token functionality, allowing you to differentiate between permanently registered users and temporary guest sessions without separating them into different tables.
*   `2026_02_26_200645_add_user_id_to_calculations_table.php`
    *   **Description**: Alters the existing `calculations` table to link records to specific users.
    *   **Change**: Adds a `user_id` foreign key column (positioned right after the `id` column). It is nullable and has a `cascadeOnDelete` constraint—meaning that if a user or guest account is deleted from the database, all their linked calculations will be automatically cleaned up and deleted along with it.

## JavaScript

### math.js

> Is used on the frontend (Vue application) to securely and accurately parse and evaluate mathematical expressions entered by the user.

Key advantages of using `math.js` in this project:
1. **Security**: It evaluates mathematical string inputs (like `10 + 20` or complex nested brackets like `sqrt((((9*9)/12)+(13-4))*2)^2)`) securely without relying on JavaScript's native and dangerous `eval()` function, eliminating XSS and code injection risks on the client side.
2. **Advanced Functions**: It provides robust out-of-the-box support for advanced mathematical operations (such as square roots `sqrt()` and exponents `^`), complex expressions, and correct order of operations.
3. **Precision**: Standard JavaScript floating-point arithmetic is notoriously inaccurate (e.g., `0.1 + 0.2` yielding `0.30000000000000004`). `math.js` provides tools for precise calculations, preventing visual bugs when users perform decimal calculations.


## Rules / CS Fixer

- /.php-cs-fixer.dist.php
- /pint.json
- /.cursorrules

**braces_position** = next_line_unless_newline_at_signature_end
> When defining functions { and } are on their own lines (for readability)

**fully_qualified_strict_types** = true
> All types must be fully qualified (for readability)

**global_namespace_import** = true
> All imports must be in the global namespace (for readability)
