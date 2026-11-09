<?php
namespace App\Services;

/** A Payme Merchant API protocol error (always returned with HTTP 200 inside a JSON-RPC envelope). */
class PaymeError extends \RuntimeException
{
    private const MESSAGES = [
        -32504 => ['ru' => 'Недостаточно привилегий для выполнения метода', 'uz' => 'Usulni bajarish uchun huquq yetarli emas', 'en' => 'Insufficient privilege to perform this method'],
        -32601 => ['ru' => 'Метод не найден', 'uz' => 'Usul topilmadi', 'en' => 'Method not found'],
        -32600 => ['ru' => 'Неверный запрос', 'uz' => 'Noto‘g‘ri so‘rov', 'en' => 'Invalid request'],
        -31001 => ['ru' => 'Неверная сумма', 'uz' => 'Noto‘g‘ri summa', 'en' => 'Wrong amount'],
        -31003 => ['ru' => 'Транзакция не найдена', 'uz' => 'Tranzaksiya topilmadi', 'en' => 'Transaction not found'],
        -31007 => ['ru' => 'Невозможно отменить: заказ выполнен', 'uz' => 'Bekor qilib bo‘lmaydi: buyurtma bajarilgan', 'en' => 'Cannot cancel: the order is already fulfilled'],
        -31008 => ['ru' => 'Невозможно выполнить операцию', 'uz' => 'Amalni bajarib bo‘lmaydi', 'en' => 'Cannot perform the operation'],
        -31050 => ['ru' => 'Счёт не найден', 'uz' => 'Hisob topilmadi', 'en' => 'Invoice not found'],
    ];

    public function __construct(public readonly int $rpcCode, public readonly ?string $data = null)
    {
        parent::__construct(self::MESSAGES[$rpcCode]['en'] ?? 'Error', $rpcCode);
    }

    public function toArray(): array
    {
        return ['code' => $this->rpcCode, 'message' => self::MESSAGES[$this->rpcCode] ?? ['en' => 'Error'], 'data' => $this->data];
    }
}
