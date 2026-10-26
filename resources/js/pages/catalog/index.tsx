import { Head, Link } from '@inertiajs/react';
import type {
    PublicActorSummary,
    PublicChannelSummary,
    PublicVideoSummary,
} from '@/types';

type Props = {
    channels: PublicChannelSummary[];
    videos: PublicVideoSummary[];
    actors: PublicActorSummary[];
};

export default function CatalogIndex({ channels, videos, actors }: Props) {
    return (
        <>
            <Head title="공개 카탈로그 — Filmo" />

            <div className="mx-auto w-full max-w-6xl px-4 py-12 md:px-6">
                <div className="max-w-2xl">
                    <p className="text-sm font-medium tracking-wide text-muted-foreground uppercase">
                        Public Catalog
                    </p>
                    <h1 className="mt-3 text-3xl font-semibold tracking-tight md:text-4xl">
                        채널, 영상, 배우를 한눈에.
                    </h1>
                    <p className="mt-3 text-muted-foreground">
                        Filmo에 수집된 공개 기록입니다. 항목을 선택하면 상세
                        정보와 연결된 기록을 볼 수 있습니다.
                    </p>
                </div>

                <CatalogSection
                    title="채널"
                    description="영상을 수집한 YouTube 채널"
                    empty="아직 등록된 채널이 없습니다."
                    isEmpty={channels.length === 0}
                >
                    <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        {channels.map((channel) => (
                            <Link
                                key={channel.id}
                                href={`/catalog/channels/${channel.id}`}
                                className="rounded-lg border border-border/70 bg-card p-4 transition hover:border-primary/40"
                            >
                                <p className="font-medium">{channel.title}</p>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    {channel.handle ?? '핸들 없음'} · 영상{' '}
                                    {channel.video_count}개
                                </p>
                            </Link>
                        ))}
                    </div>
                </CatalogSection>

                <CatalogSection
                    title="영상"
                    description="최근 공개된 순서"
                    empty="아직 수집된 영상이 없습니다."
                    isEmpty={videos.length === 0}
                >
                    <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        {videos.map((video) => (
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
                </CatalogSection>

                <CatalogSection
                    title="배우"
                    description="이름순"
                    empty="아직 등록된 배우가 없습니다."
                    isEmpty={actors.length === 0}
                >
                    <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        {actors.map((actor) => (
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
                </CatalogSection>
            </div>
        </>
    );
}

function CatalogSection({
    title,
    description,
    empty,
    isEmpty,
    children,
}: {
    title: string;
    description: string;
    empty: string;
    isEmpty: boolean;
    children: React.ReactNode;
}) {
    return (
        <section className="mt-12">
            <div className="mb-4 flex items-end justify-between gap-4">
                <div>
                    <h2 className="text-xl font-semibold tracking-tight">
                        {title}
                    </h2>
                    <p className="mt-1 text-sm text-muted-foreground">
                        {description}
                    </p>
                </div>
            </div>

            {isEmpty ? (
                <p className="rounded-lg border border-dashed border-border/80 bg-muted/30 p-6 text-sm text-muted-foreground">
                    {empty}
                </p>
            ) : (
                children
            )}
        </section>
    );
}

function formatDate(value: string | null): string {
    if (!value) {
        return '공개일 없음';
    }

    return new Date(value).toLocaleDateString('ko-KR');
}
