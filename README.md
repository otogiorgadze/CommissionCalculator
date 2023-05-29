# Commission Calculator
This code provides a CommissionCalculator class that calculates commission fees for deposit and withdrawal operations based on predefined rules. It uses currency exchange rates from an external API to convert amounts to Euros for calculation.

## Requirements
PHP version 7.0 or above
Composer (for autoloading dependencies)
###Installation
Clone the repository or download the code files.

Install dependencies using Composer by running the following command in the project root directory:

Copy code
composer install
## Usage
Include the necessary autoload file at the beginning of your PHP file:

php
Copy code
require 'vendor/autoload.php';
Create an instance of the CommissionCalculator class:

php
Copy code
$calculator = new CommissionCalculator();
Call the calculateCommissionFees method and pass the path to the input file containing the operations:

php
Copy code
$commissionFees = $calculator->calculateCommissionFees('input.csv');
The method will return an array of commission fees for each operation.

Iterate over the commissionFees array and process the calculated fees:

php
Copy code
foreach ($commissionFees as $fee) {
echo $fee . "\n";
}
## Configuration
The CommissionCalculator class has several configurable properties that can be modified according to your requirements:

$commisionRateForPrivateWithdraw: Commission rate (as a decimal) for private withdrawals (default: 0.3).
$commisionRateForBusinessWithdraw: Commission rate (as a decimal) for business withdrawals (default: 0.5).
$commisionRateForDeposit: Commission rate (as a decimal) for deposits (default: 0.03).
$maxAmount: Maximum amount (in Euros) allowed for private withdrawals (default: 1000).
$maxWithdrawalPerWeek: Maximum number of withdrawals allowed per week for private withdrawals (default: 3).
Feel free to adjust these properties to match your desired commission calculation rules.

## External API
The code utilizes an external API to fetch currency exchange rates. The API used is https://developers.paysera.com/tasks/api/currency-exchange-rates. The method fetchCurrencyRates retrieves the rates and stores them for further calculations. In case the API request fails or the response cannot be decoded, an exception will be thrown.

## Limitations
The code assumes that the input file (input.csv) contains the necessary information in the correct order: date, user ID, user type, operation type, amount, and currency. Make sure the input file follows this format.
The code doesn't include error handling for invalid input values or edge cases. It is recommended to add appropriate error handling and validation based on your specific needs.
## License
This code is provided under the MIT License. Feel free to modify and use it according to your requirements.