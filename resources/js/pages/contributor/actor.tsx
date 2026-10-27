import { Head, Link, useForm } from '@inertiajs/react';
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
        video_ids: actor.videos.map((video) => video.id),
        new_video_urls: '',
    });

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
                            new_video_urls: data.new_video_urls
                                .split('\n')
                                .map((url) => url.trim())
                                .filter((url) => url !== ''),
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

                    <div className="grid gap-3">
                        <Label>필모그래피 영상</Label>
                        <div className="grid max-h-64 gap-2 overflow-y-auto rounded-md border border-border/70 p-3">
                            {videos.map((video) => (
                                <label
                                    key={video.id}
                                    className="flex items-start gap-2 text-sm"
                                >
                                    <input
                                        type="checkbox"
                                        checked={form.data.video_ids.includes(
                                            video.id,
                                        )}
                                        onChange={(event) => {
                                            const videoIds = event.target
                                                .checked
                                                ? [
                                                      ...form.data.video_ids,
                                                      video.id,
                                                  ]
                                                : form.data.video_ids.filter(
                                                      (id) =>
                                                          id !== video.id,
                                                  );
                                            form.setData(
                                                'video_ids',
                                                videoIds,
                                            );
                                        }}
                                        className="mt-1"
                                    />
                                    <span>
                                        {video.title}
                                        <span className="block text-xs text-muted-foreground">
                                            {video.channel ?? '채널 미등록'}
                                        </span>
                                    </span>
                                </label>
                            ))}
                        </div>
                        <Label htmlFor="new-videos">
                            새 영상 URL (한 줄에 하나)
                        </Label>
                        <textarea
                            id="new-videos"
                            className="min-h-20 rounded-md border border-input bg-transparent px-3 py-2 text-sm"
                            value={form.data.new_video_urls}
                            onChange={(event) =>
                                form.setData(
                                    'new_video_urls',
                                    event.target.value,
                                )
                            }
                            placeholder="https://www.youtube.com/watch?v=..."
                        />
                        <InputError message={form.errors.new_video_urls} />
                    </div>

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
