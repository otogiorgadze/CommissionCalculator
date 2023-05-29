<?php

// Autoloading using Composer
require 'vendor/autoload.php';

use Carbon\Carbon;
use GuzzleHttp\Client;

class CommissionCalculator
{
    private $rates;
    private $privateWithdrawals;
    private $businessWithdrawals;
    private $commisionRateForPrivateWithdraw = 0.3;
    private $commisionRateForBusinessWithdraw = 0.5;
    private $commisionRateForDeposit = 0.03;
    private $maxAmount = 1000;
    private $maxWithdrawalPerWeek = 3;

    public function __construct()
    {
        $this->rates = $this->fetchCurrencyRates();
        $this->privateWithdrawals = [];
        $this->businessWithdrawals = [];
    }

    private function fetchCurrencyRates()
    {
        $url = 'https://developers.paysera.com/tasks/api/currency-exchange-rates';

        $client = new Client();
        $response = $client->get($url);

        $statusCode = $response->getStatusCode();
        if ($statusCode !== 200) {
            throw new Exception('Failed to fetch currency rates. HTTP code: ' . $statusCode);
        }

        $data = json_decode($response->getBody(), true);
        if ($data === null) {
            throw new Exception('Failed to decode response');
        }

        return $data['rates'];
    }



    private function convertToEuros($amount, $currency)
    {
        if ($currency === 'EUR') {
            return $amount;
        }
        if (!isset($this->rates[$currency])) {
            throw new Exception("Exchange rate not found for currency: $currency");
        }

        $rate = $this->rates[$currency];
        return $amount * $rate; // Multiply the amount by the exchange rate
    }

    private function processDeposit($amount, $currency)
    {
        $amountInEuros = $this->convertToEuros($amount, $currency);
        $commission = $this->calculatePercentage($amountInEuros, $this->commisionRateForDeposit);
        return $commission;
    }
    private function calculatePercentage($value, $percentage)
    {
        if ($percentage < 0 || $percentage > 100) {
            throw new Exception("Percentage must be between 0 and 100");
        }

        return number_format(($value * $percentage) / 100, 2, '.', '');
    }

    private function processPrivateWithdrawal($amount, $currency, $date, $userId)
    {
        $amountInEuros = $this->convertToEuros($amount, $currency);

        // Check if the user has already made three withdrawals this week and count the sum
        [$weeklyWithdrawalCount, $totalWithdrawalAmount] = $this->getWeeklyWithdrawalCount($date, 'private', $userId);
        $totalWithdrawalAmountIncludingCurrent = $totalWithdrawalAmount + $amountInEuros;
        if ($weeklyWithdrawalCount < $this->maxWithdrawalPerWeek && $totalWithdrawalAmountIncludingCurrent <= $this->maxAmount) {
            $commission = number_format(0, 2, '.', '');
        }else if($weeklyWithdrawalCount <= $this->maxWithdrawalPerWeek){
            if($amountInEuros > $this->maxAmount) {
                $amountInEuros -= $this->maxAmount;
            }
            $commission = $this->calculatePercentage($amountInEuros, $this->commisionRateForPrivateWithdraw);

        } else {
            $commission = $this->calculatePercentage($amountInEuros, $this->commisionRateForPrivateWithdraw);
        }

        $this->privateWithdrawals[$date][] = [
            'userId' => $userId,
            'amount' => $amountInEuros,
        ];


        return $commission;
    }

    private function getWeeklyWithdrawalCount($date, $userType, $userId)
    {
        $startDate = Carbon::parse($date)->startOfWeek()->format('Y-m-d');
        $endDate = Carbon::parse($date)->endOfWeek()->format('Y-m-d');
        $withdrawals = ($userType === 'private') ? $this->privateWithdrawals : $this->businessWithdrawals;

        $count = 0;
        $totalAmount = 0;
        foreach ($withdrawals as $weekStart => $withdrawalData) {
            if ($weekStart >= $startDate && $weekStart <= $endDate) {
                foreach ($withdrawalData as $withdrawal) {
                    $withdrawalUserId = $withdrawal['userId'];
                    $withdrawalAmount = $withdrawal['amount'];

                    if ($withdrawalUserId === $userId) {
                        $count++;
                        $totalAmount += $withdrawalAmount;
                    }
                }
            }
        }
        return [$count, $totalAmount];
    }




    private function processBusinessWithdrawal($amount, $currency)
    {
        $amountInEuros = $this->convertToEuros($amount, $currency);
        $commission = $this->calculatePercentage($amountInEuros, $this->commisionRateForBusinessWithdraw);
        return $commission;
    }

    public function calculateCommissionFees($filePath)
    {
        $file = fopen($filePath, 'r');
        if (!$file) {
            throw new Exception("Failed to open the input file.");
        }

        $commissionFees = [];

        while (($line = fgetcsv($file)) !== false) {
            $date = $line[0];
            $userId = $line[1];
            $userType = $line[2];
            $operationType = $line[3];
            $amount = $line[4];
            $currency = $line[5];

            switch ($operationType) {
                case 'deposit':
                    $commission = $this->processDeposit($amount, $currency);
                    break;
                case 'withdraw':
                    if ($userType === 'private') {
                        $commission = $this->processPrivateWithdrawal($amount, $currency, $date, $userId);
                    } else {
                        $commission = $this->processBusinessWithdrawal($amount, $currency, $date, $userId);
                    }
                    break;
                default:
                    throw new Exception("Invalid operation type: $operationType");
            }

            $commissionFees[] = $commission;
        }

        fclose($file);

        return $commissionFees;
    }
}

// Example usage
$calculator = new CommissionCalculator();
$commissionFees = $calculator->calculateCommissionFees('input.csv');

foreach ($commissionFees as $fee) {
    echo $fee . "\n";
}
