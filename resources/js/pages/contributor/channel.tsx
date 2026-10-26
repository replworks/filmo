import { Head, Link, useForm } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Channel = {
    id: number;
    title: string;
    handle: string | null;
    uploads_playlist_id: string | null;
    is_active: boolean;
};

type Props = {
    status?: string | null;
    channel: Channel;
};

export default function ContributorChannel({ status, channel }: Props) {
    const form = useForm({
        title: channel.title,
        handle: channel.handle ?? '',
        uploads_playlist_id: channel.uploads_playlist_id ?? '',
        is_active: channel.is_active,
    });

    return (
        <>
            <Head title={`${channel.title} 편집 — Filmo`} />

            <div className="mx-auto w-full max-w-2xl px-4 py-16 md:px-6">
                <Link
                    href="/contributor"
                    className="text-sm text-muted-foreground hover:text-foreground"
                >
                    ← Contributor Workspace
                </Link>

                <h1 className="mt-6 text-3xl font-semibold tracking-tight">
                    채널 편집
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
                        form.patch(`/contributor/channels/${channel.id}`, {
                            preserveScroll: true,
                        });
                    }}
                >
                    <div className="grid gap-2">
                        <Label htmlFor="title">채널 이름</Label>
                        <Input
                            id="title"
                            value={form.data.title}
                            onChange={(event) =>
                                form.setData('title', event.target.value)
                            }
                        />
                        <InputError message={form.errors.title} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="handle">핸들</Label>
                        <Input
                            id="handle"
                            value={form.data.handle}
                            onChange={(event) =>
                                form.setData('handle', event.target.value)
                            }
                        />
                        <InputError message={form.errors.handle} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="uploads">Uploads playlist ID</Label>
                        <Input
                            id="uploads"
                            value={form.data.uploads_playlist_id}
                            onChange={(event) =>
                                form.setData(
                                    'uploads_playlist_id',
                                    event.target.value,
                                )
                            }
                        />
                        <InputError
                            message={form.errors.uploads_playlist_id}
                        />
                    </div>

                    <label className="flex items-center gap-2 text-sm">
                        <input
                            type="checkbox"
                            checked={form.data.is_active}
                            onChange={(event) =>
                                form.setData('is_active', event.target.checked)
                            }
                        />
                        활성 채널
                    </label>

                    <Button type="submit" disabled={form.processing}>
                        {form.processing ? '저장 중…' : '저장'}
                    </Button>
                </form>
            </div>
        </>
    );
}
