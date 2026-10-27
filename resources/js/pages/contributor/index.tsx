import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Auth } from '@/types';

type ContributionSummary = {
    id: number;
    status: string;
    type: string;
    record_id: number;
    record_title: string;
    payload: Record<string, unknown>;
    created_at: string | null;
    review_note: string | null;
};

type ChannelRequestSummary = {
    id: number;
    input: string;
    status: string;
    created_at: string | null;
    review_note: string | null;
};

type Props = {
    status?: string | null;
    contributions: ContributionSummary[];
    channelRequests: ChannelRequestSummary[];
};

export default function ContributorWorkspace({
    status,
    contributions,
    channelRequests,
}: Props) {
    const { auth } = usePage<{ auth: Auth }>().props;
    const { errors } = usePage<{ errors: { conflict?: string } }>().props;
    const user = auth.user;

    const channelForm = useForm({ channel: '' });
    const videoForm = useForm({ url: '' });
    const actorForm = useForm({ name: '', activity_name: '' });

    return (
        <>
            <Head title="Contributor Workspace — Filmo" />

            <div className="mx-auto w-full max-w-5xl px-4 py-16 md:px-6">
                <p className="text-sm font-medium tracking-wide text-muted-foreground uppercase">
                    Contributor Workspace
                </p>
                <h1 className="mt-3 text-3xl font-semibold tracking-tight">
                    {user ? `${user.name}님` : '기여자 워크스페이스'}
                </h1>
                <p className="mt-4 text-muted-foreground">
                    채널·영상·배우 정보를 기여할 수 있습니다. 운영자 승인 후
                    카탈로그에 공개됩니다.
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

                {errors.conflict && (
                    <p className="mt-8 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                        {errors.conflict}
                    </p>
                )}

                <section className="mt-10 rounded-xl border border-border/70 bg-card p-6">
                    <h2 className="text-lg font-semibold">채널 추가 요청</h2>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Channel ID, @handle, 또는 YouTube 채널 URL을 입력해 추가를 요청하세요. 운영자 승인 후 채널과 영상이 추가됩니다.
                    </p>

                    <form
                        className="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end"
                        onSubmit={(event) => {
                            event.preventDefault();
                            channelForm.post('/contributor/channel-requests', {
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
                                ? '제출 중…'
                                : '채널 기여'}
                        </Button>
                    </form>
                </section>

                <section className="mt-6 rounded-xl border border-border/70 bg-card p-6">
                    <h2 className="text-lg font-semibold">영상 기여</h2>
                    <p className="mt-1 text-sm text-muted-foreground">
                        YouTube 영상 URL을 입력하면 영상과 채널 정보를 가져와
                        기여로 제출합니다.
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
                            {videoForm.processing ? '제출 중…' : '영상 기여'}
                        </Button>
                    </form>
                </section>

                <section className="mt-6 rounded-xl border border-border/70 bg-card p-6">
                    <h2 className="text-lg font-semibold">배우 기여</h2>
                    <p className="mt-1 text-sm text-muted-foreground">
                        이름만으로 제출할 수 있습니다. 나머지 정보는 운영자
                        승인 후 수정 기여로 보완할 수 있습니다.
                    </p>

                    <form
                        className="mt-4 grid gap-3 sm:grid-cols-[1fr_1fr_auto] sm:items-end"
                        onSubmit={(event) => {
                            event.preventDefault();
                            actorForm.post('/contributor/actors', {
                                preserveScroll: true,
                                onSuccess: () => actorForm.reset(),
                            });
                        }}
                    >
                        <div className="grid gap-2">
                            <Label htmlFor="actor-name">이름</Label>
                            <Input
                                id="actor-name"
                                value={actorForm.data.name}
                                onChange={(event) =>
                                    actorForm.setData(
                                        'name',
                                        event.target.value,
                                    )
                                }
                            />
                            <InputError message={actorForm.errors.name} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="actor-activity">활동명</Label>
                            <Input
                                id="actor-activity"
                                value={actorForm.data.activity_name}
                                onChange={(event) =>
                                    actorForm.setData(
                                        'activity_name',
                                        event.target.value,
                                    )
                                }
                            />
                        </div>
                        <Button
                            type="submit"
                            disabled={actorForm.processing}
                        >
                            {actorForm.processing ? '제출 중…' : '배우 기여'}
                        </Button>
                    </form>
                </section>

                <section className="mt-10">
                    <h2 className="text-xl font-semibold">내 채널 요청</h2>
                    {channelRequests.length === 0 ? (
                        <p className="mt-4 rounded-lg border border-dashed border-border/80 bg-muted/30 p-6 text-sm text-muted-foreground">
                            아직 제출한 채널 요청이 없습니다.
                        </p>
                    ) : (
                        <ul className="mt-4 divide-y rounded-lg border border-border/70 bg-card">
                            {channelRequests.map((request) => (
                                <li
                                    key={request.id}
                                    className="flex flex-col gap-2 px-4 py-4 sm:flex-row sm:items-center sm:justify-between"
                                >
                                    <div>
                                        <p className="font-medium break-all">
                                            {request.input}
                                        </p>
                                        <p className="mt-1 text-sm text-muted-foreground">
                                            {statusLabel(request.status)}
                                            {request.review_note
                                                ? ` · ${request.review_note}`
                                                : ''}
                                        </p>
                                    </div>
                                </li>
                            ))}
                        </ul>
                    )}
                </section>

                <section className="mt-10">
                    <h2 className="text-xl font-semibold">내 기여</h2>
                    <p className="mt-1 text-sm text-muted-foreground">
                        승인 전에는 공개 카탈로그에 표시되지 않습니다.
                    </p>

                    {contributions.length === 0 ? (
                        <p className="mt-4 rounded-lg border border-dashed border-border/80 bg-muted/30 p-6 text-sm text-muted-foreground">
                            아직 제출한 기여가 없습니다.
                        </p>
                    ) : (
                        <ul className="mt-4 divide-y rounded-lg border border-border/70 bg-card">
                            {contributions.map((contribution) => (
                                <li
                                    key={contribution.id}
                                    className="flex flex-col gap-2 px-4 py-4 sm:flex-row sm:items-center sm:justify-between"
                                >
                                    <div>
                                        <p className="font-medium">
                                            {contribution.type} ·{' '}
                                            {contribution.record_title}
                                        </p>
                                        <p className="mt-1 text-sm text-muted-foreground">
                                            {statusLabel(contribution.status)}
                                            {contribution.review_note
                                                ? ` · ${contribution.review_note}`
                                                : ''}
                                        </p>
                                    </div>

                                    {contribution.status === 'pending' && (
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            onClick={() =>
                                                router.delete(
                                                    `/contributor/contributions/${contribution.id}`,
                                                    { preserveScroll: true },
                                                )
                                            }
                                        >
                                            기여 삭제
                                        </Button>
                                    )}
                                </li>
                            ))}
                        </ul>
                    )}
                </section>
            </div>
        </>
    );
}

function statusLabel(status: string): string {
    return (
        {
            pending: '운영자 검토 대기',
            approved: '승인됨',
            rejected: '거절됨',
        }[status] ?? status
    );
}
