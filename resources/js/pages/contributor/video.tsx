import { Head, Link, useForm } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Video = {
    id: number;
    title: string;
    description: string | null;
    published_at: string | null;
    duration: string | null;
    thumbnail_url: string | null;
    view_count: number | null;
    like_count: number | null;
    comment_count: number | null;
    unavailable_at: string | null;
    channel: string | null;
};

type Props = {
    status?: string | null;
    video: Video;
};

export default function ContributorVideo({ status, video }: Props) {
    const form = useForm({
        title: video.title,
        description: video.description ?? '',
        published_at: video.published_at ?? '',
        duration: video.duration ?? '',
        thumbnail_url: video.thumbnail_url ?? '',
        view_count: video.view_count?.toString() ?? '',
        like_count: video.like_count?.toString() ?? '',
        comment_count: video.comment_count?.toString() ?? '',
        unavailable_at: video.unavailable_at ?? '',
    });

    return (
        <>
            <Head title={`${video.title} 편집 — Filmo`} />

            <div className="mx-auto w-full max-w-3xl px-4 py-16 md:px-6">
                <Link
                    href="/contributor"
                    className="text-sm text-muted-foreground hover:text-foreground"
                >
                    ← Contributor Workspace
                </Link>

                <h1 className="mt-6 text-3xl font-semibold tracking-tight">
                    영상 편집
                </h1>
                <p className="mt-2 text-sm text-muted-foreground">
                    {video.channel ?? '채널 정보 없음'}
                </p>

                {status && (
                    <p className="mt-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                        {status}
                    </p>
                )}

                <form
                    className="mt-8 grid gap-5 rounded-xl border border-border/70 bg-card p-6"
                    onSubmit={(event) => {
                        event.preventDefault();
                        form.patch(`/contributor/videos/${video.id}`, {
                            preserveScroll: true,
                        });
                    }}
                >
                    <Field label="제목" error={form.errors.title}>
                        <Input
                            value={form.data.title}
                            onChange={(event) =>
                                form.setData('title', event.target.value)
                            }
                        />
                    </Field>

                    <Field label="설명" error={form.errors.description}>
                        <textarea
                            className="min-h-24 rounded-md border border-input bg-transparent px-3 py-2 text-sm"
                            value={form.data.description}
                            onChange={(event) =>
                                form.setData('description', event.target.value)
                            }
                        />
                    </Field>

                    <Field label="공개일" error={form.errors.published_at}>
                        <Input
                            type="datetime-local"
                            value={form.data.published_at}
                            onChange={(event) =>
                                form.setData(
                                    'published_at',
                                    event.target.value,
                                )
                            }
                        />
                    </Field>

                    <Field label="길이" error={form.errors.duration}>
                        <Input
                            value={form.data.duration}
                            onChange={(event) =>
                                form.setData('duration', event.target.value)
                            }
                        />
                    </Field>

                    <Field
                        label="썸네일 URL"
                        error={form.errors.thumbnail_url}
                    >
                        <Input
                            value={form.data.thumbnail_url}
                            onChange={(event) =>
                                form.setData(
                                    'thumbnail_url',
                                    event.target.value,
                                )
                            }
                        />
                    </Field>

                    <div className="grid gap-4 sm:grid-cols-3">
                        <Field label="조회수" error={form.errors.view_count}>
                            <Input
                                type="number"
                                value={form.data.view_count}
                                onChange={(event) =>
                                    form.setData(
                                        'view_count',
                                        event.target.value,
                                    )
                                }
                            />
                        </Field>
                        <Field label="좋아요" error={form.errors.like_count}>
                            <Input
                                type="number"
                                value={form.data.like_count}
                                onChange={(event) =>
                                    form.setData(
                                        'like_count',
                                        event.target.value,
                                    )
                                }
                            />
                        </Field>
                        <Field label="댓글" error={form.errors.comment_count}>
                            <Input
                                type="number"
                                value={form.data.comment_count}
                                onChange={(event) =>
                                    form.setData(
                                        'comment_count',
                                        event.target.value,
                                    )
                                }
                            />
                        </Field>
                    </div>

                    <Field
                        label="사용 불가 시각"
                        error={form.errors.unavailable_at}
                    >
                        <Input
                            type="datetime-local"
                            value={form.data.unavailable_at}
                            onChange={(event) =>
                                form.setData(
                                    'unavailable_at',
                                    event.target.value,
                                )
                            }
                        />
                    </Field>

                    <Button type="submit" disabled={form.processing}>
                        {form.processing ? '저장 중…' : '저장'}
                    </Button>
                </form>
            </div>
        </>
    );
}

function Field({
    label,
    error,
    children,
}: {
    label: string;
    error?: string;
    children: React.ReactNode;
}) {
    return (
        <div className="grid gap-2">
            <Label>{label}</Label>
            {children}
            <InputError message={error} />
        </div>
    );
}
