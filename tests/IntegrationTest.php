<?php declare(strict_types=1);

namespace danog\Decoder\Test;

use CURLFile;
use danog\Decoder\FileId;
use danog\Decoder\FileIdType;
use danog\Decoder\UniqueFileId;
use PHPUnit\Framework\TestCase;

/** @api */
class IntegrationTest extends TestCase
{
    /**
     * @dataProvider provideFileIdsAndType
     */
    public function testAll(FileIdType $type, string $fileIdStr, string $uniqueFileIdStr): void
    {
        $fileId = FileId::fromBotAPI($fileIdStr);
        $this->assertSame($type, $fileId->type);

        $this->assertSame($fileIdStr, $fileId->getBotAPI());

        $uniqueFileId = UniqueFileId::fromUniqueBotAPI($uniqueFileIdStr);
        $this->assertSame($type->toUnique(), $uniqueFileId->type);
        $this->assertSame($uniqueFileIdStr, $uniqueFileId->getUniqueBotAPI());

        $this->assertSame($uniqueFileIdStr, $fileId->getUnique()->getUniqueBotAPI());
    }

    /** @psalm-suppress MixedArrayAccess, MixedAssignment, PossiblyInvalidArgument, MixedArgument */
    public function provideFileIdsAndType(): \Generator
    {
        foreach (['CAADBAADwwADmFmqDf6xBrPTReqHFgQ', 'CAACAgQAAxkBAAIC4l9CWDGzVUcDejU0TETLWbOdfsCoAALDAAOYWaoN_rEGs9NF6ocbBA', 'CAADBAADwwADmFmqDf6xBrPTReqHAg'] as $fileId) {
            yield [
                FileIdType::STICKER,
                $fileId,
                'AgADwwADmFmqDQ'
            ];
        }

        $dest = \getenv('DEST');
        $token = \getenv('TOKEN');
        foreach ($this->provideChats() as $chat) {
            /**
             * @var array{
             *      small_file_id: string,
             *      small_file_unique_id: string,
             *      big_file_id: string,
             *      big_file_unique_id: string
             * }
             */
            $result = \json_decode(\file_get_contents("https://api.telegram.org/bot$token/getChat?chat_id=$chat"), true)['result']['photo'];
            yield [
                FileIdType::PROFILE_PHOTO,
                $result['small_file_id'],
                $result['small_file_unique_id'],
            ];
            yield [
                FileIdType::fromBotApiType('profile_photo'),
                $result['big_file_id'],
                $result['big_file_unique_id'],
            ];
        }
        foreach ($this->provideUrls() as $type => $url) {
            if ($type === 'video_note') {
                \copy($url, \basename($url));

                $handle = \curl_init("https://api.telegram.org/bot$token/sendVideoNote?chat_id=$dest");
                \curl_setopt($handle, CURLOPT_POST, true);
                \curl_setopt($handle, CURLOPT_POSTFIELDS, [
                    $type => new CURLFile(\basename($url))
                ]);
                \curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
                $botResult = \json_decode(\curl_exec($handle), true);
                \curl_close($handle);

                \unlink(\basename($url));
            } else {
                $botResult = \json_decode(\file_get_contents("https://api.telegram.org/bot$token/send$type?chat_id=$dest&$type=$url"), true);
            }
            $botResult = $botResult['result'][$type];
            if ($type !== 'photo') {
                $botResult = [$botResult];
            }
            foreach ($botResult as $subResult) {
                /** @var string $type */
                yield [
                    FileIdType::fromBotApiType($type),
                    $subResult['file_id'],
                    $subResult['file_unique_id']
                ];
                if (isset($subResult['thumb'])) {
                    yield [
                        FileIdType::fromBotApiType('thumbnail'),
                        $subResult['thumb']['file_id'],
                        $subResult['thumb']['file_unique_id']
                    ];
                }
            }
        }
    }
    /**
     * @psalm-suppress InvalidReturnStatement, InvalidReturnType
     * @return list<string>
     */
    public function provideChats(): array
    {
        return [\getenv('DEST'), '@MadelineProto'];
    }
    public function provideUrls(): array
    {
        return [
            'sticker' => 'https://github.com/danog/MadelineProto/raw/v8/tests/lel.webp?raw=true',
            'photo' => 'https://github.com/danog/MadelineProto/raw/v8/tests/faust.jpg',
            'audio' => 'https://github.com/danog/MadelineProto/raw/v8/tests/mosconi.mp3?raw=true',
            'video' => 'https://github.com/danog/MadelineProto/raw/v8/tests/swing.mp4?raw=true',
            'animation' => 'https://github.com/danog/MadelineProto/raw/v8/tests/pony.mp4?raw=true',
            'document' => 'https://github.com/danog/danog.github.io/raw/master/lol/index_htm_files/0.gif',
            'voice' => 'https://daniil.it/audio_2020-02-01_18-09-08.ogg',
            'video_note' => 'https://daniil.it/round.mp4'
        ];
    }
}
