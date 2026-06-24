<?php

return [
    'required' => ':attributeを入力してください。',
    'string' => ':attributeは文字列で入力してください。',
    'email' => ':attributeは有効なメールアドレス形式で入力してください。',
    'max' => [
        'string' => ':attributeは:max文字以内で入力してください。',
    ],
    'min' => [
        'string' => ':attributeは:min文字以上で入力してください。',
    ],
    'confirmed' => ':attributeと確認用の入力が一致しません。',
    'unique' => ':attributeはすでに使用されています。',

    'attributes' => [
        'name' => '名前',
        'email' => 'メールアドレス',
        'password' => 'パスワード',
    ],
];