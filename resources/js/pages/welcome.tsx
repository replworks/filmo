import { Head, Link, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import type {
    Auth,
    PublicActorSummary,
    PublicVideoSummary,
} from '@/types';

type Props = {
    featuredActors: PublicActorSummary[];
    featuredVideos: PublicVideoSummary[];
};

export default function Welcome({ featuredActors, featuredVideos }: Props) {
    const { auth } = usePage<{ auth: Auth }>().props;

    return (
        <>
            <Head title="Filmo — 배우 필모그래피 카탈로그" />

            <section className="mx-auto w-full max-w-6xl px-4 py-16 md:px-6 md:py-24">
                <div className="max-w-3xl">
                    <p className="mb-4 text-sm font-medium tracking-wide text-muted-foreground uppercase">
                        YouTube 웹드라마 · 스케치 코미디
                    </p>
                    <h1 className="text-4xl font-semibold tracking-tight text-balance md:text-6xl">
                        배우의 필모그래피를
                        <br />
                        한 곳에서.
                    </h1>
                    <p className="mt-6 max-w-2xl text-lg leading-8 text-muted-foreground">
                        Filmo는 YouTube 영상에 출연한 배우와 작품을 연결해
                        쌓아가는 카탈로그입니다. 배우를 찾고, 어떤 영상에
                        출연했는지 확인해 보세요.
                    </p>

                    <div className="mt-8 flex flex-wrap items-center gap-3">
                        <Button size="lg" asChild>
                            <Link href="/catalog">카탈로그 둘러보기</Link>
                        </Button>

                        {auth.user ? (
                            <Button variant="outline" size="lg" asChild>
                                <Link href="/contributor">
                                    Contributor Workspace
                                </Link>
                            </Button>
                        ) : (
                            <>
                                <Button variant="outline" size="lg" asChild>
                                    <Link href="/login">로그인</Link>
                                </Button>
                                <Button variant="ghost" size="lg" asChild>
                                    <Link href="/register">회원가입</Link>
                                </Button>
                            </>
                        )}
                    </div>
                </div>
            </section>

            <section className="mx-auto w-full max-w-6xl px-4 pb-16 md:px-6">
                <div className="mb-6 flex items-end justify-between gap-4">
                    <div>
                        <h2 className="text-2xl font-semibold tracking-tight">
                            최근 추가된 배우
                        </h2>
                        <p className="mt-1 text-sm text-muted-foreground">
                            새로 카탈로그에 등록된 배우입니다.
                        </p>
                    </div>
                    <Link
                        href="/catalog"
                        className="text-sm font-medium text-primary hover:underline"
                    >
                        전체 보기
                    </Link>
                </div>

                {featuredActors.length === 0 ? (
                    <EmptyState message="아직 등록된 배우가 없습니다." />
                ) : (
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {featuredActors.map((actor) => (
                            <Link
                                key={actor.id}
                                href={`/catalog/actors/${actor.id}`}
                                className="rounded-xl border border-border/70 bg-card p-5 transition hover:border-primary/40 hover:shadow-sm"
                            >
                                <p className="text-lg font-semibold">
                                    {actor.name}
                                </p>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    {actor.activity_name ?? '활동명 없음'}
                                </p>
                            </Link>
                        ))}
                    </div>
                )}
            </section>

            <section className="mx-auto w-full max-w-6xl px-4 pb-20 md:px-6">
                <div className="mb-6 flex items-end justify-between gap-4">
                    <div>
                        <h2 className="text-2xl font-semibold tracking-tight">
                            최근 수집된 영상
                        </h2>
                        <p className="mt-1 text-sm text-muted-foreground">
                            채널에서 새로 수집된 영상입니다.
                        </p>
                    </div>
                    <Link
                        href="/catalog"
                        className="text-sm font-medium text-primary hover:underline"
                    >
                        전체 보기
                    </Link>
                </div>

                {featuredVideos.length === 0 ? (
                    <EmptyState message="아직 수집된 영상이 없습니다." />
                ) : (
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {featuredVideos.map((video) => (
                            <Link
                                key={video.id}
                                href={`/catalog/videos/${video.id}`}
                                className="rounded-xl border border-border/70 bg-card p-5 transition hover:border-primary/40 hover:shadow-sm"
                            >
                                <p className="line-clamp-2 font-semibold">
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
        </>
    );
}

function EmptyState({ message }: { message: string }) {
    return (
        <div className="rounded-xl border border-dashed border-border/80 bg-muted/30 p-8 text-center text-sm text-muted-foreground">
            {message}
        </div>
    );
}

function formatDate(value: string | null): string {
    if (!value) {
        return '공개일 없음';
    }

    return new Date(value).toLocaleDateString('ko-KR');
}
