import { Head, Link, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import type { Auth } from '@/types';

export default function ContributorWorkspace() {
    const { auth } = usePage<{ auth: Auth }>().props;
    const user = auth.user;

    return (
        <>
            <Head title="Contributor Workspace — Filmo" />

            <div className="mx-auto w-full max-w-4xl px-4 py-16 md:px-6">
                <p className="text-sm font-medium tracking-wide text-muted-foreground uppercase">
                    Contributor Workspace
                </p>
                <h1 className="mt-3 text-3xl font-semibold tracking-tight">
                    {user ? `${user.name}님` : '기여자 워크스페이스'}
                </h1>
                <p className="mt-4 text-muted-foreground">
                    채널·영상·배우를 추가하고 편집하는 공간입니다. 공개
                    카탈로그에서 현재 기록을 확인할 수 있습니다.
                </p>

                <div className="mt-8 flex flex-wrap gap-3">
                    <Button asChild>
                        <Link href="/catalog">공개 카탈로그</Link>
                    </Button>
                </div>

                <div className="mt-10 rounded-xl border border-dashed border-border/80 bg-muted/30 p-6">
                    <h2 className="text-lg font-semibold">다음 단계</h2>
                    <p className="mt-2 text-sm text-muted-foreground">
                        채널과 영상 추가, 배우 편집 기능이 이곳에 추가됩니다.
                    </p>
                </div>
            </div>
        </>
    );
}
