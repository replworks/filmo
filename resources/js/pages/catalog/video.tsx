import { Head, Link } from '@inertiajs/react';
import type { PublicVideoDetail } from '@/types';

type Props = {
    video: PublicVideoDetail;
};

export default function CatalogVideo({ video }: Props) {
    return (
        <>
            <Head title={`${video.title} — Filmo`} />

            <div className="mx-auto w-full max-w-5xl px-4 py-12 md:px-6">
                <Link
                    href="/catalog"
                    className="text-sm text-muted-foreground hover:text-foreground"
                >
                    ← 카탈로그
                </Link>

                <div className="mt-6 max-w-3xl">
                    <h1 className="text-3xl font-semibold tracking-tight">
                        {video.title}
                    </h1>
                    <p className="mt-2 text-sm text-muted-foreground">
                        {video.channel ? (
                            <Link
                                href={`/catalog/channels/${video.channel.id}`}
                                className="hover:text-foreground hover:underline"
                            >
                                {video.channel.title}
                            </Link>
                        ) : (
                            '채널 정보 없음'
                        )}
                        {' · '}
                        {formatDate(video.published_at)}
                    </p>

                    <a
                        href={video.youtube_url}
                        target="_blank"
                        rel="noreferrer"
                        className="mt-4 inline-flex text-sm font-medium text-primary hover:underline"
                    >
                        YouTube에서 보기 ↗
                    </a>
                </div>

                <section className="mt-10">
                    <h2 className="text-xl font-semibold">출연 배우</h2>

                    {video.actors.length === 0 ? (
                        <p className="mt-4 rounded-lg border border-dashed border-border/80 bg-muted/30 p-6 text-sm text-muted-foreground">
                            아직 연결된 배우가 없습니다.
                        </p>
                    ) : (
                        <div className="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            {video.actors.map((actor) => (
                                <Link
                                    key={actor.id}
                                    href={`/catalog/actors/${actor.id}`}
                                    className="rounded-lg border border-border/70 bg-card p-4 transition hover:border-primary/40"
                                >
                                    <p className="font-medium">{actor.name}</p>
                                    <p className="mt-1 text-sm text-muted-foreground">
                                        {actor.activity_name ?? '활동명 없음'}
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
