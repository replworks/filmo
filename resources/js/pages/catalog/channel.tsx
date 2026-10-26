import { Head, Link } from '@inertiajs/react';
import type { PublicChannelDetail } from '@/types';

type Props = {
    channel: PublicChannelDetail;
};

export default function CatalogChannel({ channel }: Props) {
    return (
        <>
            <Head title={`${channel.title} — Filmo`} />

            <div className="mx-auto w-full max-w-5xl px-4 py-12 md:px-6">
                <Link
                    href="/catalog"
                    className="text-sm text-muted-foreground hover:text-foreground"
                >
                    ← 카탈로그
                </Link>

                <div className="mt-6">
                    <h1 className="text-3xl font-semibold tracking-tight">
                        {channel.title}
                    </h1>
                    <p className="mt-2 text-sm text-muted-foreground">
                        {channel.handle ?? '핸들 없음'} · 영상{' '}
                        {channel.video_count}개
                    </p>
                </div>

                <section className="mt-10">
                    <h2 className="text-xl font-semibold">영상</h2>

                    {channel.videos.length === 0 ? (
                        <p className="mt-4 rounded-lg border border-dashed border-border/80 bg-muted/30 p-6 text-sm text-muted-foreground">
                            이 채널에는 아직 공개된 영상이 없습니다.
                        </p>
                    ) : (
                        <div className="mt-4 grid gap-3 sm:grid-cols-2">
                            {channel.videos.map((video) => (
                                <Link
                                    key={video.id}
                                    href={`/catalog/videos/${video.id}`}
                                    className="rounded-lg border border-border/70 bg-card p-4 transition hover:border-primary/40"
                                >
                                    <p className="line-clamp-2 font-medium">
                                        {video.title}
                                    </p>
                                    <p className="mt-2 text-sm text-muted-foreground">
                                        {formatDate(video.published_at)}
                                    </p>
                                </Link>
                            ))}
                        </div>
                    )}
                </section>
            </div>
        </>
    );
}

function formatDate(value: string | null): string {
    if (!value) {
        return '공개일 없음';
    }

    return new Date(value).toLocaleDateString('ko-KR');
}
