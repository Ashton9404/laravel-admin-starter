<?php

/*
 * Only the rules this application actually uses are translated. Laravel falls
 * back to the fallback locale per key, so anything missing here still renders
 * in English rather than leaking a raw translation key.
 */
return [
    'accepted' => ':attribute 必須接受。',
    'boolean' => ':attribute 必須是 true 或 false。',
    'confirmed' => ':attribute 兩次輸入不一致。',
    'email' => ':attribute 必須是有效的 Email 位址。',
    'exists' => '所選的 :attribute 無效。',
    'in' => '所選的 :attribute 無效。',
    'integer' => ':attribute 必須是整數。',
    'max' => [
        'array' => ':attribute 不能超過 :max 個項目。',
        'file' => ':attribute 不能大於 :max KB。',
        'numeric' => ':attribute 不能大於 :max。',
        'string' => ':attribute 不能超過 :max 個字元。',
    ],
    'min' => [
        'array' => ':attribute 至少需要 :min 個項目。',
        'file' => ':attribute 至少需要 :min KB。',
        'numeric' => ':attribute 不能小於 :min。',
        'string' => ':attribute 至少需要 :min 個字元。',
    ],
    'required' => ':attribute 為必填。',
    'string' => ':attribute 必須是文字。',
    'unique' => ':attribute 已經被使用了。',

    'password' => [
        'letters' => ':attribute 必須包含至少一個字母。',
        'mixed' => ':attribute 必須包含大小寫字母各至少一個。',
        'numbers' => ':attribute 必須包含至少一個數字。',
        'symbols' => ':attribute 必須包含至少一個符號。',
        'uncompromised' => '這個 :attribute 曾出現在資料外洩事件中,請換一個。',
    ],

    'attributes' => [
        'name' => '名稱',
        'email' => 'Email',
        'password' => '密碼',
        'password_confirmation' => '確認密碼',
        'roles' => '角色',
        'locale' => '語言',
        'search' => '搜尋',
        'role' => '角色',
        'sort' => '排序',
        'direction' => '排序方向',
        'per_page' => '每頁筆數',
        'token' => '權杖',
    ],
];
