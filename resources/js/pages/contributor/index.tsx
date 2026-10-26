import { Head, Link, useForm, usePage } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Auth } from '@/types';

type Props = {
    status?: string | null;
};

export default function ContributorWorkspace({ status }: Props) {
    const { auth } = usePage<{ auth: Auth }>().props;
    const user = auth.user;

    const channelForm = useForm({ channel: '' });
    const videoForm = useForm({ url: '' });

    return (
        <>
            <Head title="Contributor Workspace — Filmo" />

            <div className="mx-auto w-full max-w-4xl px-4 py-16 md:px-6">
                <p className="text-sm font-medium tracking-wide text-muted-foreground uppercase">
                    Contributor Workspace
                </p>
                <h1 className="mt-3 text-3xl font-semibold tracking-tight">
                    {user ? `${user.name}님` : '기여자 워크스페이스'}
                </h1>
                <p className="mt-4 text-muted-foreground">
                    채널과 영상을 추가하고, 공개 카탈로그에서 기록을 확인할 수
                    있습니다.
                </p>

                <div className="mt-8 flex flex-wrap gap-3">
                    <Button variant="outline" asChild>
                        <Link href="/catalog">공개 카탈로그</Link>
                    </Button>
                </div>

                {status && (
                    <p className="mt-8 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                        {status}
                    </p>
                )}

                <section className="mt-10 rounded-xl border border-border/70 bg-card p-6">
                    <h2 className="text-lg font-semibold">채널 추가</h2>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Channel ID, @handle, 또는 YouTube 채널 URL을 입력하세요.
                        채널을 추가하면 초기 영상 수집이 시작됩니다.
                    </p>

                    <form
                        className="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end"
                        onSubmit={(event) => {
                            event.preventDefault();
                            channelForm.post('/contributor/channels', {
                                preserveScroll: true,
                                onSuccess: () => channelForm.reset(),
                            });
                        }}
                    >
                        <div className="grid flex-1 gap-2">
                            <Label htmlFor="channel">채널</Label>
                            <Input
                                id="channel"
                                value={channelForm.data.channel}
                                onChange={(event) =>
                                    channelForm.setData(
                                        'channel',
                                        event.target.value,
                                    )
                                }
                                placeholder="@YouTube 또는 https://youtube.com/@YouTube"
                            />
                            <InputError message={channelForm.errors.channel} />
                        </div>
                        <Button
                            type="submit"
                            disabled={channelForm.processing}
                        >
                            {channelForm.processing
                                ? '추가 중…'
                                : '채널 추가'}
                        </Button>
                    </form>
                </section>

                <section className="mt-6 rounded-xl border border-border/70 bg-card p-6">
                    <h2 className="text-lg font-semibold">영상 추가</h2>
                    <p className="mt-1 text-sm text-muted-foreground">
                        YouTube 영상 URL을 입력하면 영상과 채널 정보를 가져와
                        카탈로그에 연결합니다.
                    </p>

                    <form
                        className="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end"
                        onSubmit={(event) => {
                            event.preventDefault();
                            videoForm.post('/contributor/videos', {
                                preserveScroll: true,
                                onSuccess: () => videoForm.reset(),
                            });
                        }}
                    >
                        <div className="grid flex-1 gap-2">
                            <Label htmlFor="url">YouTube URL</Label>
                            <Input
                                id="url"
                                value={videoForm.data.url}
                                onChange={(event) =>
                                    videoForm.setData(
                                        'url',
                                        event.target.value,
                                    )
                                }
                                placeholder="https://www.youtube.com/watch?v=..."
                            />
                            <InputError message={videoForm.errors.url} />
                        </div>
                        <Button
                            type="submit"
                            disabled={videoForm.processing}
                        >
                            {videoForm.processing ? '추가 중…' : '영상 추가'}
                        </Button>
                    </form>
                </section>
            </div>
        </>
    );
}
