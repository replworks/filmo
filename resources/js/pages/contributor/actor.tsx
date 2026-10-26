import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type VideoSummary = {
    id: number;
    title: string;
    channel: string | null;
};

type Actor = {
    id: number;
    name: string;
    activity_name: string | null;
    birth_date: string | null;
    birth_place: string | null;
    education: string | null;
    debut: string | null;
    agency: string | null;
    nationality: string | null;
    profile: Record<string, string>;
    reference_urls: string[];
    videos: VideoSummary[];
};

type Props = {
    status?: string | null;
    actor: Actor;
    videos: VideoSummary[];
};

type Row = {
    key: string;
    value: string;
};

export default function ContributorActor({ status, actor, videos }: Props) {
    const [profileRows, setProfileRows] = useState<Row[]>(() =>
        Object.entries(actor.profile).map(([key, value]) => ({ key, value })),
    );

    const form = useForm({
        name: actor.name,
        activity_name: actor.activity_name ?? '',
        birth_date: actor.birth_date ?? '',
        birth_place: actor.birth_place ?? '',
        education: actor.education ?? '',
        debut: actor.debut ?? '',
        agency: actor.agency ?? '',
        nationality: actor.nationality ?? '',
        reference_urls: actor.reference_urls.length > 0
            ? actor.reference_urls
            : [''],
        profile: actor.profile,
    });

    const videoForm = useForm({ video_id: '' });

    const availableVideos = videos.filter(
        (video) => !actor.videos.some((attached) => attached.id === video.id),
    );

    const updateProfileRows = (rows: Row[]) => {
        setProfileRows(rows);
        form.setData(
            'profile',
            Object.fromEntries(
                rows
                    .filter((row) => row.key.trim() !== '')
                    .map((row) => [row.key.trim(), row.value]),
            ),
        );
    };

    return (
        <>
            <Head title={`${actor.name} 편집 — Filmo`} />

            <div className="mx-auto w-full max-w-3xl px-4 py-16 md:px-6">
                <Link
                    href="/contributor"
                    className="text-sm text-muted-foreground hover:text-foreground"
                >
                    ← Contributor Workspace
                </Link>

                <h1 className="mt-6 text-3xl font-semibold tracking-tight">
                    배우 편집
                </h1>

                {status && (
                    <p className="mt-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                        {status}
                    </p>
                )}

                <form
                    className="mt-8 grid gap-5 rounded-xl border border-border/70 bg-card p-6"
                    onSubmit={(event) => {
                        event.preventDefault();
                        form.transform((data) => ({
                            ...data,
                            profile: Object.fromEntries(
                                profileRows
                                    .filter((row) => row.key.trim() !== '')
                                    .map((row) => [
                                        row.key.trim(),
                                        row.value,
                                    ]),
                            ),
                            reference_urls: data.reference_urls.filter(
                                (url) => url.trim() !== '',
                            ),
                        }));

                        form.patch(`/contributor/actors/${actor.id}`, {
                            preserveScroll: true,
                        });
                    }}
                >
                    <div className="grid gap-2">
                        <Label htmlFor="name">이름</Label>
                        <Input
                            id="name"
                            value={form.data.name}
                            onChange={(event) =>
                                form.setData('name', event.target.value)
                            }
                        />
                        <InputError message={form.errors.name} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="activity">활동명</Label>
                        <Input
                            id="activity"
                            value={form.data.activity_name}
                            onChange={(event) =>
                                form.setData(
                                    'activity_name',
                                    event.target.value,
                                )
                            }
                        />
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <Field label="생년월일" error={form.errors.birth_date}>
                            <Input
                                type="date"
                                value={form.data.birth_date}
                                onChange={(event) =>
                                    form.setData(
                                        'birth_date',
                                        event.target.value,
                                    )
                                }
                            />
                        </Field>
                        <Field label="출생지" error={form.errors.birth_place}>
                            <Input
                                value={form.data.birth_place}
                                onChange={(event) =>
                                    form.setData(
                                        'birth_place',
                                        event.target.value,
                                    )
                                }
                            />
                        </Field>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="education">학력 / 졸업 학교</Label>
                        <textarea
                            id="education"
                            className="min-h-20 rounded-md border border-input bg-transparent px-3 py-2 text-sm"
                            value={form.data.education}
                            onChange={(event) =>
                                form.setData('education', event.target.value)
                            }
                        />
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <Field label="데뷔" error={form.errors.debut}>
                            <Input
                                value={form.data.debut}
                                onChange={(event) =>
                                    form.setData('debut', event.target.value)
                                }
                            />
                        </Field>
                        <Field label="소속사" error={form.errors.agency}>
                            <Input
                                value={form.data.agency}
                                onChange={(event) =>
                                    form.setData('agency', event.target.value)
                                }
                            />
                        </Field>
                        <Field label="국적" error={form.errors.nationality}>
                            <Input
                                value={form.data.nationality}
                                onChange={(event) =>
                                    form.setData(
                                        'nationality',
                                        event.target.value,
                                    )
                                }
                            />
                        </Field>
                    </div>

                    <div className="grid gap-3">
                        <Label>배우 URL</Label>
                        {form.data.reference_urls.map((url, index) => (
                            <div
                                key={index}
                                className="flex items-center gap-2"
                            >
                                <Input
                                    value={url}
                                    placeholder="https://namu.wiki/..."
                                    onChange={(event) => {
                                        const next = [
                                            ...form.data.reference_urls,
                                        ];
                                        next[index] = event.target.value;
                                        form.setData('reference_urls', next);
                                    }}
                                />
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    onClick={() =>
                                        form.setData(
                                            'reference_urls',
                                            form.data.reference_urls.filter(
                                                (_, i) => i !== index,
                                            ),
                                        )
                                    }
                                >
                                    삭제
                                </Button>
                            </div>
                        ))}
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={() =>
                                form.setData('reference_urls', [
                                    ...form.data.reference_urls,
                                    '',
                                ])
                            }
                        >
                            URL 추가
                        </Button>
                    </div>

                    <div className="grid gap-3">
                        <Label>기타 정보</Label>
                        {profileRows.map((row, index) => (
                            <div
                                key={index}
                                className="grid gap-2 sm:grid-cols-[1fr_2fr_auto]"
                            >
                                <Input
                                    value={row.key}
                                    placeholder="항목"
                                    onChange={(event) => {
                                        const next = [...profileRows];
                                        next[index] = {
                                            ...next[index],
                                            key: event.target.value,
                                        };
                                        updateProfileRows(next);
                                    }}
                                />
                                <Input
                                    value={row.value}
                                    placeholder="값"
                                    onChange={(event) => {
                                        const next = [...profileRows];
                                        next[index] = {
                                            ...next[index],
                                            value: event.target.value,
                                        };
                                        updateProfileRows(next);
                                    }}
                                />
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    onClick={() =>
                                        updateProfileRows(
                                            profileRows.filter(
                                                (_, i) => i !== index,
                                            ),
                                        )
                                    }
                                >
                                    삭제
                                </Button>
                            </div>
                        ))}
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={() =>
                                updateProfileRows([
                                    ...profileRows,
                                    { key: '', value: '' },
                                ])
                            }
                        >
                            항목 추가
                        </Button>
                    </div>

                    <Button type="submit" disabled={form.processing}>
                        {form.processing ? '저장 중…' : '저장'}
                    </Button>
                </form>

                <section className="mt-8 rounded-xl border border-border/70 bg-card p-6">
                    <h2 className="text-lg font-semibold">출연 영상</h2>

                    {actor.videos.length === 0 ? (
                        <p className="mt-3 text-sm text-muted-foreground">
                            아직 연결된 영상이 없습니다.
                        </p>
                    ) : (
                        <ul className="mt-3 divide-y">
                            {actor.videos.map((video) => (
                                <li
                                    key={video.id}
                                    className="flex items-center justify-between gap-4 py-3"
                                >
                                    <div>
                                        <p className="font-medium">
                                            {video.title}
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            {video.channel ?? '채널 정보 없음'}
                                        </p>
                                    </div>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        onClick={() =>
                                            router.delete(
                                                `/contributor/actors/${actor.id}/videos/${video.id}`,
                                                { preserveScroll: true },
                                            )
                                        }
                                    >
                                        연결 해제
                                    </Button>
                                </li>
                            ))}
                        </ul>
                    )}

                    <form
                        className="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end"
                        onSubmit={(event) => {
                            event.preventDefault();
                            videoForm.post(
                                `/contributor/actors/${actor.id}/videos`,
                                {
                                    preserveScroll: true,
                                    onSuccess: () => videoForm.reset(),
                                },
                            );
                        }}
                    >
                        <div className="grid flex-1 gap-2">
                            <Label htmlFor="video">영상 추가</Label>
                            <select
                                id="video"
                                className="h-9 rounded-md border border-input bg-transparent px-3 text-sm"
                                value={videoForm.data.video_id}
                                onChange={(event) =>
                                    videoForm.setData(
                                        'video_id',
                                        event.target.value,
                                    )
                                }
                            >
                                <option value="">영상 선택</option>
                                {availableVideos.map((video) => (
                                    <option
                                        key={video.id}
                                        value={video.id.toString()}
                                    >
                                        {video.title}
                                    </option>
                                ))}
                            </select>
                            <InputError message={videoForm.errors.video_id} />
                        </div>
                        <Button
                            type="submit"
                            disabled={videoForm.processing}
                        >
                            추가
                        </Button>
                    </form>
                </section>
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
