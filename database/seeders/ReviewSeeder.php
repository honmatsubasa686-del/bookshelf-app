<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::whereIn('email', [
            'yamada@example.com',
            'suzuki@example.com',
            'tanaka@example.com',
            'sato@example.com',
            'takahashi@example.com',
        ])->get()->keyBy('email');

        $books = Book::whereIn('isbn', [
            '9784101010014',
            '9784422100524',
            '9784873115658',
            '9784863940246',
            '9784101010021',
            '9784309226712',
            '9784048930598',
            '9784478025819',
            '9784163902302',
            '9784822289607',
            '9784822251468',
        ])->get()->keyBy('isbn');

        if ($users->count() <5 || $books->count() < 11) {
            return;
        }

        $reviews = [
            [
                'email' => 'yamada@example.com',
                'isbn' => '9784101010014',
                'rating' => 4,
                'comment' => '猫の視点から人間を見る語り口が面白く、時代を超えて楽しめました。',
            ],
            [
                'email' => 'suzuki@example.com',
                'isbn' => '9784101010014',
                'rating' => 5,
                'comment' => '皮肉の効いた文章が印象的で、日本文学の入り口としてとても読みやすい一冊でした。',
            ],
            [
                'email' => 'tanaka@example.com',
                'isbn' => '9784101010014',
                'rating' => 3,
                'comment' => '少し古い表現ではありますが、独特のユーモアがあり最後まで読めました。',
            ],
            [
                'email' => 'sato@example.com',
                'isbn' => '9784422100524',
                'rating' => 5,
                'comment' => '人との接し方を見直すきっかけになる内容で、実生活でも役立ちそうです。',
            ],
            [
                'email' => 'takahashi@example.com',
                'isbn' => '9784422100524',
                'rating' => 4,
                'comment' => '具体例が多く、コミュニケーションの基本を学び直すのに良い本でした。',
            ],
            [
                'email' => 'yamada@example.com',
                'isbn' => '9784422100524',
                'rating' => 4,
                'comment' => '仕事でも家庭でも使える考え方が多く、何度も読み返したくなります。',
            ],
            [
                'email' => 'suzuki@example.com',
                'isbn' => '9784873115658',
                'rating' => 5,
                'comment' => 'コードを書くときに読み手を意識する大切さがよく分かりました。',
            ],
            [
                'email' => 'tanaka@example.com',
                'isbn' => '9784873115658',
                'rating' => 4,
                'comment' => '変数名やコメントの考え方など、すぐに実践できる内容が多かったです。',
            ],
            [
                'email' => 'sato@example.com',
                'isbn' => '9784873115658',
                'rating' => 5,
                'comment' => '初学者にも中級者にも役立つ、読みやすいコードの教科書だと思います。',
            ],
            [
                'email' => 'takahashi@example.com',
                'isbn' => '9784863940246',
                'rating' => 4,
                'comment' => '自分の行動や考え方を見直すきっかけになりました。',
            ],
            [
                'email' => 'yamada@example.com',
                'isbn' => '9784863940246',
                'rating' => 5,
                'comment' => '仕事だけでなく人生全体に活かせる考え方がまとまっていました。',
            ],
            [
                'email' => 'suzuki@example.com',
                'isbn' => '9784863940246',
                'rating' => 4,
                'comment' => '少しボリュームはありますが、読み進めるほど納得感がありました。',
            ],
            [
                'email' => 'tanaka@example.com',
                'isbn' => '9784101010021',
                'rating' => 4,
                'comment' => '主人公のまっすぐさが気持ちよく、テンポよく読めました。',
            ],
            [
                'email' => 'sato@example.com',
                'isbn' => '9784101010021',
                'rating' => 3,
                'comment' => '時代背景を感じる部分もありますが、人物描写が印象に残りました。',
            ],
            [
                'email' => 'takahashi@example.com',
                'isbn' => '9784101010021',
                'rating' => 4,
                'comment' => '短めで読みやすく、夏目漱石の作品に触れる入口として良かったです。',
            ],
            [
                'email' => 'yamada@example.com',
                'isbn' => '9784309226712',
                'rating' => 5,
                'comment' => '人類史を大きな流れで理解でき、視野が広がる内容でした。',
            ],
            [
                'email' => 'suzuki@example.com',
                'isbn' => '9784309226712',
                'rating' => 4,
                'comment' => '歴史と科学の話がつながっていて、知的好奇心を刺激されました。',
            ],
            [
                'email' => 'tanaka@example.com',
                'isbn' => '9784309226712',
                'rating' => 5,
                'comment' => '当たり前だと思っていた社会の仕組みを見直すきっかけになりました。',
            ],
            [
                'email' => 'sato@example.com',
                'isbn' => '9784048930598',
                'rating' => 5,
                'comment' => '良いコードとは何かを深く考えさせられる、開発者向けの名著です。',
            ],
            [
                'email' => 'takahashi@example.com',
                'isbn' => '9784048930598',
                'rating' => 4,
                'comment' => '内容は少し硬めですが、保守しやすいコードを書く意識が高まりました。',
            ],
            [
                'email' => 'yamada@example.com',
                'isbn' => '9784048930598',
                'rating' => 4,
                'comment' => '実務でコードを書く人ほど刺さる内容が多いと感じました。',
            ],
            [
                'email' => 'suzuki@example.com',
                'isbn' => '9784478025819',
                'rating' => 5,
                'comment' => '対話形式で読みやすく、自分の考え方を見つめ直すきっかけになりました。',
            ],
            [
                'email' => 'tanaka@example.com',
                'isbn' => '9784478025819',
                'rating' => 4,
                'comment' => '人間関係の悩みに対して、違う角度から考えるヒントをもらえました。',
            ],
            [
                'email' => 'sato@example.com',
                'isbn' => '9784478025819',
                'rating' => 4,
                'comment' => '読みやすい一方で、内容は深く考えさせられる本でした。',
            ],
            [
                'email' => 'takahashi@example.com',
                'isbn' => '9784163902302',
                'rating' => 4,
                'comment' => '芸人の世界の厳しさと人間関係が丁寧に描かれていました。',
            ],
            [
                'email' => 'yamada@example.com',
                'isbn' => '9784163902302',
                'rating' => 3,
                'comment' => '淡々とした文章の中に、登場人物の感情がにじんでいました。',
            ],
            [
                'email' => 'suzuki@example.com',
                'isbn' => '9784163902302',
                'rating' => 4,
                'comment' => '青春小説としても楽しめ、読後に余韻が残る作品でした。',
            ],
            [
                'email' => 'tanaka@example.com',
                'isbn' => '9784822289607',
                'rating' => 5,
                'comment' => '思い込みではなくデータで世界を見る大切さを学べました。',
            ],
            [
                'email' => 'sato@example.com',
                'isbn' => '9784822289607',
                'rating' => 5,
                'comment' => '世界の見方が変わる内容で、ニュースを見る目も変わりました。',
            ],
            [
                'email' => 'takahashi@example.com',
                'isbn' => '9784822289607',
                'rating' => 4,
                'comment' => '図や例が分かりやすく、楽しく学べるビジネス教養書でした。',
            ],
            [
                'email' => 'yamada@example.com',
                'isbn' => '9784822251468',
                'rating' => 4,
                'comment' => 'コンテナという身近でないテーマから、世界経済の変化が見えて面白かったです。',
            ],
            [
                'email' => 'suzuki@example.com',
                'isbn' => '9784822251468',
                'rating' => 3,
                'comment' => '物流の歴史に興味がある人には刺さる内容だと思います。',
            ],
        ];

        foreach ($reviews as $review) {
            Review::create ([
                'user_id' => $users[$review['email']]->id,
                'book_id' => $books[$review['isbn']]->id,
                'rating' => $review['rating'],
                'comment' => $review['comment'],
            ]);
        }
    }
}
