import { Head, Link } from '@inertiajs/react';
import type { PublicActorDetail } from '@/types';

type Props = {
    actor: PublicActorDetail;
};

export default function CatalogActor({ actor }: Props) {
    return (
        <>
            <Head title={`${actor.name} — Filmo`} />

            <div className="mx-auto w-full max-w-5xl px-4 py-12 md:px-6">
                <Link
                    href="/catalog"
                    className="text-sm text-muted-foreground hover:text-foreground"
                >
                    ← 카탈로그
                </Link>

                <div className="mt-6">
                    <h1 className="text-3xl font-semibold tracking-tight">
                        {actor.name}
                    </h1>
                    <p className="mt-1 text-muted-foreground">
                        {actor.activity_name ?? '활동명 없음'}
                    </p>
                </div>

                <section className="mt-8">
                    <h2 className="text-xl font-semibold">배우 정보</h2>
                    <dl className="mt-4 grid gap-4 rounded-lg border border-border/70 bg-card p-5 sm:grid-cols-2">
                        <Info label="생년월일" value={actor.birth_date} />
                        <Info label="출생지" value={actor.birth_place} />
                        <Info label="학력" value={actor.education} />
                        <Info label="데뷔" value={actor.debut} />
                        <Info label="소속사" value={actor.agency} />
                        <Info label="국적" value={actor.nationality} />
                    </dl>
                </section>

                {actor.reference_urls.length > 0 && (
                    <section className="mt-8">
                        <h2 className="text-xl font-semibold">참고 링크</h2>
                        <ul className="mt-3 flex flex-wrap gap-3">
                            {actor.reference_urls.map((url) => (
                                <li key={url}>
                                    <a
                                        href={url}
                                        target="_blank"
                                        rel="noreferrer"
                                        className="text-sm font-medium text-primary hover:underline"
                                    >
                                        {url} ↗
                                    </a>
                                </li>
                            ))}
                        </ul>
                    </section>
                )}

                <section className="mt-10">
                    <h2 className="text-xl font-semibold">출연 영상</h2>

                    {actor.videos.length === 0 ? (
                        <p className="mt-4 rounded-lg border border-dashed border-border/80 bg-muted/30 p-6 text-sm text-muted-foreground">
                            아직 연결된 영상이 없습니다.
                        </p>
                    ) : (
                        <div className="mt-4 grid gap-3 sm:grid-cols-2">
                            {actor.videos.map((video) => (
                                <Link
                                    key={video.id}
                                    href={`/catalog/videos/${video.id}`}
                                    className="rounded-lg border border-border/70 bg-card p-4 transition hover:border-primary/40"
                                >
                                    <p className="line-clamp-2 font-medium">
                                        {video.title}
                                    </p>
                                    <p className="mt-2 text-sm text-muted-foreground">
                                        {video.channel?.title ?? '채널 정보 없음'}
                                    </p>
                                    <p className="mt-1 text-xs text-muted-foreground">
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

function Info({ label, value }: { label: string; value: string | null }) {
    return (
        <div>
            <dt className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                {label}
            </dt>
            <dd className="mt-1 text-sm">{value ?? '—'}</dd>
        </div>
    );
}

function formatDate(value: string | null): string {
    if (!value) {
        return '공개일 없음';
    }

    return new Date(value).toLocaleDateString('ko-KR');
}
